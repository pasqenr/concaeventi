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
     * Get all the associations.
     *
     * @return array The associations.
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
     * Get the association identified by $associationID.
     *
     * @param int $associationID A valid association identifier.
     * @return array The association with its information.
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
     * Return an association ID given a valid association name $assName.
     *
     * @param string $assName A valid association name.
     * @return string The associated association ID as string.
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
     * Return an array of the associations associated to the user $userID.
     *
     * @param int $userID The user unique identifier.
     * @return array The array of the associations.
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
     * Create a new entry for the association.
     *
     * @param array $data The array with the required fields to create a new association.
     * @return bool TRUE if the association has been created, FALSE otherwise.
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
     * Update all the fields in the association identified by $associationID with the fields in
     * $data.
     *
     * @param int $associationID A valid association identifier.
     * @param array $data The array with the required fields to update the association.
     * @return bool TRUE if the association has been updated, FALSE otherwise.
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
     * Delete an association identified by $associationID.
     *
     * @param int $associationID A valid association identifier.
     * @return bool TRUE if the association has been deleted, FALSE otherwise.
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
     * Get the identifier of the last inserted association, that is, the larger
     * identifier.
     *
     * @return int The identifier of the last inserted association.
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
     * Create an association between an association identified by
     * $associationID and a user identified by $memberID.
     *
     * @param int $associationID A valid association identifier.
     * @param int $memberID A valid user identifier.
     * @return bool TRUE if the association and user are been associated, FALSE
     *         otherwise.
     * @throws \PDOException
     */
    private function addBelong($associationID, $memberID): bool
    {
        $sth = $this->db->prepare('
            INSERT INTO Appartiene (
                idUtente, idAssociazione
            ) VALUES (
                :idUtente, :idAssociazione
            )
        ');
        $sth->bindParam(':idUtente', $memberID, \PDO::PARAM_INT);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return true;
    }

    /**
     * Get all the members associated by an association identified by
     * $associationID.
     *
     * @param int $associationID A valid association identifier.
     * @return array An array of user identifier and them association
     *         identifier.
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
     * Remove the user(s) associated to the association identified by
     * $associationID.
     *
     * @param int $associationID A valid association identifier.
     * @return bool TRUE if the associations have been delete, FALSE otherwise.
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
     * Create associations between the users in $members (that stores
     * identifiers) and the association identified by $associationID.
     *
     * @param int $associationID A valid association ID.
     * @param array $members An array of member identifiers.
     * @return bool TRUE if the associations have been created, FALSE
     *         otherwise.
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
     * Delete all the associations by users and the association identified by
     * $associationID.
     *
     * @param int $associationID A valid association identifier.
     * @return bool TRUE if the associations have been deleted, FALSE
     *         otherwise.
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
     * Return an array of associations with, for every one, a list of the
     * associated users separated by comma.
     *
     * @param array $associations An array of associations and users.
     * @return array An array with associations and their list of users.
     */
    private function mergeAssociations($associations): array
    {
        if (empty($associations)) {
            return [];
        }

        $associationsWithMergedNames = [];
        $old = $associations[0];
        $assCount = \count($associations);

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