<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;

/**
 * @property Router router
 * @property \PDO db
 */
class AssociationController extends Controller
{
    public function showAll(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associations = $this->getAssociations();
        $associations = $this->mergeAssociations($associations);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/associations.twig', [
            'utente' => $user,
            'associazioni' => $associations
        ]);
    }

    public function create(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $members = $this->getAllMembers();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/create.twig', [
            'utente' => $user,
            'membri' => $members
        ]);
    }

    public function doCreate(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createAssociation($parsedBody);

        if ($created === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $user,
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('associations'));
    }

    public function edit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associationID = $args['id'];

        try {
            $association = $this->getAssociation($associationID);
            $members = $this->getAllMembers();
            $belongs = $this->getBelongsByAssociation($associationID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Errore nell\'elaborazione dei dati.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $user,
                'err' => $this->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/edit.twig', [
            'utente' => $user,
            'ass' => $association,
            'membri' => $members,
            'appartenenza' => $belongs
        ]);
    }

    public function doEdit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associationID = $args['id'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateAssociation($associationID, $parsedBody);

        if ($updated === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $user,
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('associations'));
    }

    public function delete(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associationID = $args['id'];
        try {
            $association = $this->getAssociation($associationID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Errore nell\'elaborazione dei dati.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $user,
                'err' => $this->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/delete.twig', [
            'utente' => $user,
            'ass' => $association
        ]);
    }

    public function doDelete(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];

        try {
            $this->deleteAssociation($eventID);
        } catch (\PDOException $e) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $user,
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('associations'));
    }

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

    private function getAssociations(): array
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

        return $sth->fetchAll();
    }

    private function getAllMembers(): array
    {
        $sth = $this->db->query('
            SELECT U.idUtente, U.nome, U.cognome
            FROM Utente U
        ');

        return $sth->fetchAll();
    }

    private function createAssociation($data): bool
    {
        $associationName = $data['nomeAssociazione'];
        $logo = $data['logo'] ?? '';
        $members = $data['membri'];
        $telephone = $data['telefono'] ?? '';
        $style = $data['stile'] ?? '';

        if ($associationName === '' || empty($members)) {
            $this->setErrorMessage(
                'Empty request fields.',
                'Un campo obbligatorio non è stato inserito.');

            return false;
        }

        if ($telephone !== '' && $this->isValidTelephone($telephone) === false) {
            $this->setErrorMessage(
                'Wrong telephone format.',
                'Il numero di telefono non è nel formato corretto.');

            return false;
        }

        /*$associationID = $this->getLastAssociationID() + 1;
        $styleCreated = $this->setStyle($associationID, $style);
        $style_path = WWW_PATH . '/assets/css/ass/' . $associationID . '.css';

        if ($styleCreated === false) {
            $this->setErrorMessage(
                'Impossible to write the new style CSS file.',
                'Impossibile creare lo stile associato.');

            return false;
        }*/

        if ($style !== '' && $this->isValidHex($style) === false) {
                $this->setErrorMessage(
                    'Wrong hex format.',
                    'Il colore scelto non è nel formato corretto.');

                return false;
        }

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
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile creare l\'associazione.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        try {
            $associationID = $this->getLastAssociationID();
        } catch (\PDOException $e) {
            $this->setErrorMessage(
                'PDOException, check errorInfo.',
                'Impossibile recupeare l\'ultima associazione.');

            $this->db->rollBack();

            return false;
        }

        /** @var $members int[] */
        foreach ($members as $membro) {
            try {
                $this->addBelong($associationID, $membro);
            } catch (\PDOException $e) {
                $this->setErrorMessage(
                    'PDOException, check errorInfo.',
                    'Impossibile inserire un membro nell\'associazione.');

                $this->db->rollBack();

                return false;
            }
        }

        $this->db->commit();

        return true;
    }

    private function getLastAssociationID(): int
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

    private function getAssociation($associationID): array
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

    private function getBelongsByAssociation($associationID): array
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

    private function updateAssociation($associationID, $data): bool
    {
        $associationName = $data['nomeAssociazione'];
        $logo = $data['logo'];
        $members = $data['membri'];
        $telephone = $data['telefono'] ?? '';
        $style = $data['stile'] ?? '';

        if ($associationName === '' || empty($members)) {
            $this->setErrorMessage(
                'Empty field.',
                'Un campo obbligatorio non è stato inserito.');

            return false;
        }

        if ($telephone !== '' && $this->isValidTelephone($telephone) === false) {
            $this->setErrorMessage(
                'Wrong telephone format.',
                'Il formato del numero di telefono non è valido.');

            return false;
        }

        /*$styleCreated = $this->setStyle($associationID, $style);
        $style_path = WWW_PATH . '/assets/css/ass/' . $associationID . '.css';

        if ($styleCreated === false) {
            $this->setErrorMessage(
                'Impossible to write the style CSS file.',
                'Impossibile modificare lo stile associato.');

            return false;
        }*/

        if ($style !== '' && $this->isValidHex($style) === false) {
                $this->setErrorMessage(
                    'Wrong hex format.',
                    'Il colore scelto non è nel formato corretto.');

                return false;
        }

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
            $this->setErrorMessage(
                'PDOException, check errorInfo.',
                'Impossibile modificare l\'evento.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        try {
            $this->deleteOldBelongs($associationID);
        } catch (\PDOException $e) {
            $this->setErrorMessage(
                'PDOException, check errorInfo.',
                'Impossibile modificare le precedenti appartenenze.');

            $this->db->rollBack();

            return false;
        }

        try {
            $this->createBelongs($associationID, $members);
        } catch (\PDOException $e) {
            $this->setErrorMessage(
                'PDOException, check errorInfo.',
                'Impossibile modificare le precedenti appartenenze.');

            $this->db->rollBack();

            return false;
        }

        $this->db->commit();

        return true;
    }

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

    private function deleteAssociation($associationID): bool
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
            $this->setErrorMessage(
                'PDOException, check errorInfo.',
                'Errore nell\'eliminazione dell\'associazione.',
                $sth->errorInfo());

            $this->db->rollBack();

            throw $e;
        }

        $this->db->commit();

        return $res;
    }

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
            $this->setErrorMessage(
                'PDOException, check errorInfo.',
                'Errore nell\'eliminazione dei membri appartenenti all\'associazione.',
                $sth->errorInfo());

            throw $e;
        }

        return $res;
    }

    /*private function setStyle($associationID, $style): bool
    {
        $style_path = WWW_PATH . '/assets/css/ass/' . $associationID . '.css';

        $good = file_put_contents($style_path, $style, LOCK_EX);

        return $good !== false;
    }*/

    private function isValidTelephone($telNumber): bool
    {
        return preg_match('^\d{10}$', $telNumber) !== 0;
    }

    private function isValidHex($hex): bool
    {
        return preg_match('^#(\d|[a-f]){6}$', $hex) !== 0;
    }
}