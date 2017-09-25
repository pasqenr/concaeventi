<?php

namespace App\Models;

use \App\Helpers\ErrorHelper;

/**
 * Class AssociationController
 * @package App\Models
 *
 * @property ErrorHelper errorHelper
 */
class AssociationModel extends Model
{
    /**
     * @return array
     */
    public function getAssociations(): array
    {
        $sth = $this->db->query('
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo, A.telefono,
              CONCAT_WS(\' \', U.nome, U.cognome) AS nomeUtente
            FROM Associazione A
            LEFT JOIN Appartiene AP
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
        ');

        $associations = $sth->fetchAll();

        return $this->mergeAssociations($associations);
    }

    /**
     * @param $associationID
     * @return array
     * @throws \PDOException
     */
    public function getAssociation($associationID): array
    {
        $sth = $this->db->prepare('
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo, A.telefono, A.stile
            FROM Associazione A
            WHERE A.idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);


        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return $sth->fetch();
    }

    /**
     * @param $assName
     * @return string
     * @throws \PDOException
     */
    public function getAssociationIdByName($assName): string
    {
        $sth = $this->db->prepare('
            SELECT A.idAssociazione
            FROM Associazione A
            WHERE A.nomeAssociazione LIKE :nomeAssociazione
            LIMIT 1
        ');
        $sth->bindParam(':nomeAssociazione', $assName, \PDO::PARAM_STR);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }


        return $sth->fetch()['idAssociazione'];
    }

    /**
     * @param $userID
     * @return array
     * @throws \PDOException
     */
    public function getUserAssociations($userID): array
    {
        $sth = $this->db->prepare('
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo
            FROM Associazione A
              JOIN Appartiene AP
              USING (idAssociazione)
            WHERE AP.idUtente = :idUtente
        ');
        $sth->bindParam(':idUtente', $userID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return $sth->fetchAll();
    }

    /**
     * @param $data
     * @return bool
     */
    public function createAssociation($data): bool
    {
        $associationName = $data['nomeAssociazione'];
        $logo = $data['logo'] ?? '';
        $members = $data['membri'];
        $telephone = $data['telefono'] ?? '';
        $style = $data['stile'] ?? '';

        $this->db->beginTransaction();

        $sth = $this->db->prepare('
            INSERT INTO Associazione (
              idAssociazione, nomeAssociazione, logo, telefono, stile
            ) VALUES (
              NULL, :nomeAssociazione, :logo, :telefono, :stile
            )
        ');
        $sth->bindParam(':nomeAssociazione', $associationName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);
        $sth->bindParam(':telefono', $telephone, \PDO::PARAM_STR);
        $sth->bindParam(':stile', $style, \PDO::PARAM_STR);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile creare l\'associazione.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        try {
            $associationID = $this->getLastAssociationID();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage(
                'PDOException, check errorInfo.',
                'Impossibile recupeare l\'ultima associazione.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        /** @var $members int[] */
        foreach ($members as $membro) {
            try {
                $this->addBelong($associationID, $membro);
            } catch (\PDOException $e) {
                $this->errorHelper->setErrorMessage(
                    'PDOException, check errorInfo.',
                    'Impossibile inserire un membro nell\'associazione.');

                $this->db->rollBack();

                return false;
            }
        }

        $this->db->commit();

        return true;
    }

    /**
     * @param $associationID
     * @param $data
     * @return bool
     */
    public function updateAssociation($associationID, $data): bool
    {
        $associationName = $data['nomeAssociazione'];
        $logo = $data['logo'];
        $members = $data['membri'];
        $telephone = $data['telefono'] ?? '';
        $style = $data['stile'] ?? '';

        $this->db->beginTransaction();

        $sth = $this->db->prepare('
            UPDATE Associazione A
            SET A.nomeAssociazione = :nomeAssociazione, A.logo = :logo, A.telefono = :telefono, A.stile = :stile
            WHERE A.idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':nomeAssociazione', $associationName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);
        $sth->bindParam(':telefono', $telephone, \PDO::PARAM_STR);
        $sth->bindParam(':stile', $style, \PDO::PARAM_STR);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage(
                'PDOException, check errorInfo.',
                'Impossibile modificare l\'evento.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        try {
            $this->deleteOldBelongs($associationID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage(
                'PDOException, check errorInfo.',
                'Impossibile modificare le precedenti appartenenze.');

            $this->db->rollBack();

            return false;
        }

        try {
            $this->createBelongs($associationID, $members);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage(
                'PDOException, check errorInfo.',
                'Impossibile modificare le precedenti appartenenze.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        $this->db->commit();

        return true;
    }

    /**
     * @param $associationID
     * @return bool
     * @throws \PDOException
     */
    public function deleteAssociation($associationID): bool
    {
        $this->db->beginTransaction();

        try {
            $res = $this->deleteFromBelongs($associationID);
        } catch (\PDOException $e) {
            $this->db->rollBack();

            throw $e;
        }

        $sth = $this->db->prepare('
            DELETE
            FROM Associazione
            WHERE idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $res &= $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage(
                'PDOException, check errorInfo.',
                'Errore nell\'eliminazione dell\'associazione.',
                $sth->errorInfo());

            $this->db->rollBack();

            throw $e;
        }

        $this->db->commit();

        return $res;
    }

    /**
     * @return int
     * @throws \PDOException
     */
    public function getLastAssociationID(): int
    {
        $sth = $this->db->prepare('
            SELECT A.idAssociazione
            FROM Associazione A
            ORDER BY A.idAssociazione DESC
            LIMIT 1
        ');

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return (int)$sth->fetch()['idAssociazione'];
    }

    /**
     * @param $associationID
     * @param $membro
     * @return bool
     * @throws \PDOException
     */
    private function addBelong($associationID, $membro): bool
    {
        $sth = $this->db->prepare('
            INSERT INTO Appartiene (
                idUtente, idAssociazione
            ) VALUES (
                :idUtente, :idAssociazione
            )
        ');
        $sth->bindParam(':idUtente', $membro, \PDO::PARAM_INT);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return true;
    }

    /**
     * @param $associationID
     * @return array
     * @throws \PDOException
     */
    public function getBelongsByAssociation($associationID): array
    {
        $sth = $this->db->prepare('
            SELECT AP.idUtente, AP.idAssociazione
            FROM Appartiene AP
            WHERE AP.idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return $sth->fetchAll();
    }

    /**
     * @param $associationID
     * @return bool
     * @throws \PDOException
     */
    private function deleteOldBelongs($associationID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Appartiene
            WHERE idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return true;
    }

    /**
     * @param $associationID
     * @param $members
     * @return bool
     * @throws \PDOException
     */
    private function createBelongs($associationID, $members): bool
    {
        $res = false;

        /** @var $members int[] */
        foreach ($members as $member => $memberID) {
            $sth = $this->db->prepare('
                INSERT INTO Appartiene (
                    idUtente, idAssociazione
                )
                VALUES (
                    :idUtente, :idAssociazione
                )
            ');
            $sth->bindParam(':idUtente', $memberID, \PDO::PARAM_INT);
            $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

            try {
                $res = $sth->execute();
            } catch (\PDOException $e) {
                throw $e;
            }
        }

        return $res;
    }

    /**
     * @param $associationID
     * @return bool
     * @throws \PDOException
     */
    private function deleteFromBelongs($associationID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Appartiene
            WHERE idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $res = $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage(
                'PDOException, check errorInfo.',
                'Errore nell\'eliminazione dei membri appartenenti all\'associazione.',
                $sth->errorInfo());

            throw $e;
        }

        return $res;
    }

    /**
     * @param $associations
     * @return array
     */
    private function mergeAssociations($associations): array
    {
        if (empty($associations)) {
            return [];
        }

        $associationsWithMergedNames = [];
        $old = $associations[0];
        $assCount = count($associations);

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0, $j = 0; $i < $assCount; $i++, $j++) {
            if ($old['idAssociazione'] === $associations[$i]['idAssociazione'] &&
                $old['nomeUtente'] !== $associations[$i]['nomeUtente']) {
                $associations[$i]['nomeUtente'] .= ', ' . $old['nomeUtente'];

                if ($j !== 0) {
                    $j--;
                }
            }

            $associationsWithMergedNames[$j] = $associations[$i];

            $old = $associations[$i];
        }

        return $associationsWithMergedNames;
    }
}