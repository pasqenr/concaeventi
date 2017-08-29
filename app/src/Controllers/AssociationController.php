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
    public function showAll(Request $request, Response $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $associations = $this->getAssociations();
        $associations = $this->mergeAssociations($associations);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/associations.twig', [
            'utente' => $user,
            'associazioni' => $associations
        ]);
    }

    public function create(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $members = $this->getAllMembers();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'associations/create.twig', [
            'utente' => $user,
            'membri' => $members
        ]);
    }

    public function doCreate(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
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

    public function edit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $associationID = $args['id'];

        try {
            $association = $this->getAssociation($associationID);
            $members = $this->getAllMembers();
            $belongs = $this->getBelongsByAssociation($associationID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('edit()->_(): PDOException, check errorInfo.',
                'Modifica associazione: errore nell\'elaborazione dei dati.');

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

    public function doEdit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
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

    public function delete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $associationID = $args['id'];
        try {
            $association = $this->getAssociation($associationID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('delete()->getAssociation(): PDOException, check errorInfo.',
                'Modifica associazione: errore nell\'elaborazione dei dati.');

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

    public function doDelete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
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
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo, CONCAT_WS(\' \', U.nome, U.cognome) AS nomeUtente
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
        $logo = $data['logo'];
        $members = $data['membri'];

        if ($associationName === '' || empty($members)) {
            $this->setErrorMessage(
                'createAssociation(): PDOException, check errorInfo.',
                'Creazione associazione: un campo obbligatorio non è stato compilato.');

            return false;
        }

        $sth = $this->db->prepare('
            INSERT INTO Associazione (
              idAssociazione, nomeAssociazione, logo
            ) VALUES (
              NULL, :nomeAssociazione, :logo
            )
        ');
        $sth->bindParam(':nomeAssociazione', $associationName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('createAssociation(): PDOException, check errorInfo.',
                'Creazione associazione: errore nell\'elaborazione dei dati.');

            return false;
        }

        try {
            $associationID = $this->getLastAssociationID();
        } catch (\PDOException $e) {
            $this->setErrorMessage(
                'createAssociation()->getLastAssociationID(): PDOException, check errorInfo.',
                'Creazione associazione: errore nell\'elaborazione dei dati.');

            return false;
        }

        foreach ($members as $membro) {
            try {
                $this->addBelong($associationID, $membro);
            } catch (\PDOException $e) {
                $this->setErrorMessage(
                    'createAssociation()->addBelong(): PDOException, check errorInfo.',
                    'Creazione associazione: errore nell\'elaborazione dei dati.');

                return false;
            }
        }

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
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo
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

        if ($associationName === '' || empty($members)) {
            $this->setErrorMessage(
                'updateAssociation(): PDOException, check errorInfo.',
                'Modifica associazione: un campo obbligatorio non è stato compilato.');

            return false;
        }

        $sth = $this->db->prepare('
            UPDATE Associazione A
            SET A.nomeAssociazione = :nomeAssociazione, A.logo = :logo
            WHERE A.idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':nomeAssociazione', $associationName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage(
                'updateAssociation(): PDOException, check errorInfo.',
                'Modifica associazione: errore nell\'elaborazione dei dati.');

            return false;
        }

        try {
            $this->deleteOldBelongs($associationID);
        } catch (\PDOException $e) {
            $this->setErrorMessage(
                'updateAssociation()->deleteOldBelongs(): PDOException, check errorInfo.',
                'Modifica associazione: errore nell\'elaborazione dei dati.');

            return false;
        }

        try {
            $this->createBelongs($associationID, $members);
        } catch (\PDOException $e) {
            $this->setErrorMessage(
                'updateAssociation()->createBelongs(): PDOException, check errorInfo.',
                'Modifica associazione: errore nell\'elaborazione dei dati.');

            return false;
        }

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
                $sth->execute();
            } catch (\PDOException $e) {
                throw $e;
            }
        }

        return true;
    }

    private function deleteAssociation($associationID): bool
    {
        try {
            $this->deleteFromBelongs($associationID);
        } catch (\PDOException $e) {
            throw $e;
        }

        $sth = $this->db->prepare('
            DELETE
            FROM Associazione
            WHERE idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }
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
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }
    }
}