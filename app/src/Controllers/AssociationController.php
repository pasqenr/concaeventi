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
            return $response->withRedirect($this->router->pathFor('error'));
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
        $association = $this->getAssociation($associationID);
        $members = $this->getAllMembers();
        $belongs = $this->getBelongsByAssociation($associationID);

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
            return $response->withRedirect($this->router->pathFor('error'));

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
        $association = $this->getAssociation($associationID);

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
        $this->deleteAssociation($eventID);

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
        $sth = $this->db->prepare('
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo, CONCAT_WS(\' \', U.nome, U.cognome) AS nomeUtente
            FROM Associazione A
            LEFT JOIN Appartiene AP
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
        ');
        $sth->execute();

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
        $nomeAssociazione = $data['nomeAssociazione'];
        $logo = $data['logo'];
        $membri = $data['membri'];

        $sth = $this->db->prepare('
            INSERT INTO Associazione (
              idAssociazione, nomeAssociazione, logo
            ) VALUES (
              NULL, :nomeAssociazione, :logo
            )
        ');
        $sth->bindParam(':nomeAssociazione', $nomeAssociazione, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);

        $good = $sth->execute();

        if (!$good) {
            return false;
        }

        $associationID = $this->getLastAssociationID();

        foreach ($membri as $membro) {
            $good = $this->addBelong($associationID, $membro);

            if (!$good) {
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
        $good = $sth->execute();

        if (!$good) {
            return false;
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

        return $sth->execute();
    }

    private function getAssociation($associationID): array
    {
        $sth = $this->db->prepare('
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo
            FROM Associazione A
            WHERE A.idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetch();
    }

    private function getAssociationMembers($associationID): array
    {
        $sth = $this->db->prepare('
            SELECT U.idUtente, U.nome, U.cognome
            FROM Appartiene AP
            JOIN Utente U
            USING (idUtente)
            WHERE AP.idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchAll();
    }

    private function getBelongsByAssociation($associationID): array
    {
        $sth = $this->db->prepare('
            SELECT AP.idUtente, AP.idAssociazione
            FROM Appartiene AP
            WHERE AP.idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchAll();
    }

    private function updateAssociation($associationID, $data): bool
    {
        $associationName = $data['nomeAssociazione'];
        $logo = $data['logo'];
        $members = $data['membri'];

        $sth = $this->db->prepare('
            UPDATE Associazione A
            SET A.nomeAssociazione = :nomeAssociazione, A.logo = :logo
            WHERE A.idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':nomeAssociazione', $associationName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        $good = $sth->execute();

        if (!$good) {
            return false;
        }

        $good = $this->deleteOldBelongs($associationID);

        if (!$good) {
            return false;
        }

        $good = $this->createBelongs($associationID, $members);

        return $good;
    }

    private function deleteOldBelongs($associationID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Appartiene
            WHERE idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
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
            $good = $sth->execute();

            if (!$good) {
                return false;
            }
        }

        return true;
    }

    private function deleteAssociation($associationID): bool
    {
        $good = $this->deleteFromBelongs($associationID);

        if (!$good) {
            return false;
        }

        $sth = $this->db->prepare('
            DELETE
            FROM Associazione
            WHERE idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function deleteFromBelongs($associationID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Appartiene
            WHERE idAssociazione = :idAssociazione
        ');
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
    }
}