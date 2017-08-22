<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class AssociationController extends Controller
{
    public function showAll(RequestInterface $request, ResponseInterface $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $associations = $this->getAssociations();
        $associations = $this->mergeAssociations($associations);

        return $this->render($response, 'associations/associations.twig', [
            'utente' => $user,
            'associazioni' => $associations
        ]);
    }

    public function create(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $members = $this->getAllMembers();

        return $this->render($response, 'associations/create.twig', [
            'utente' => $user,
            'membri' => $members
        ]);
    }

    public function doCreate(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createAssociation($parsedBody);

        if ($created === true) {
            return $response->withRedirect($this->router->pathFor('associations'));
        } else {
            return $response->withRedirect($this->router->pathFor('error'));
        }
    }

    public function edit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $associationID = $args['id'];
        $association = $this->getAssociation($associationID);
        $members = $this->getAllMembers();
        $belongs = $this->getBelongsByAssociation($associationID);

        return $this->render($response, 'associations/edit.twig', [
            'utente' => $user,
            'ass' => $association,
            'membri' => $members,
            'appartenenza' => $belongs
        ]);
    }

    public function doEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $associationID = $args['id'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateAssociation($associationID, $parsedBody);

        if ($updated === true) {
            return $response->withRedirect($this->router->pathFor('associations'));
        } else {
            return $response->withRedirect($this->router->pathFor('error'));
        }
    }

    public function delete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $associationID = $args['id'];
        $association = $this->getAssociation($associationID);

        return $this->render($response, 'associations/delete.twig', [
            'utente' => $user,
            'ass' => $association
        ]);
    }

    public function doDelete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $eventID = (int)$args['id'];
        $this->deleteAssociation($eventID);

        return $response->withRedirect($this->router->pathFor('associations'));
    }

    private function mergeAssociations($associations)
    {
        if (empty($associations))
            return [];

        $associationsWithMergedNames = [];
        $old = $associations[0];

        foreach ($associations as $ass) {
            if ($old['idAssociazione'] === $ass['idAssociazione'] &&
                $old['nomeUtente'] !== $ass['nomeUtente']) {
                $ass['nomeUtente'] .= ', ' . $old['nomeUtente'];
                array_pop($associationsWithMergedNames);
            }

            array_push($associationsWithMergedNames, $ass);

            $old = $ass;
        }

        return $associationsWithMergedNames;
    }

    private function getAssociations()
    {
        $sth = $this->db->prepare("
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo, CONCAT_WS(' ', U.nome, U.cognome) AS nomeUtente
            FROM Associazione A
            LEFT JOIN Appartiene AP
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
        ");
        $sth->execute();

        return $sth->fetchAll();
    }

    private function getAllMembers()
    {
        $sth = $this->db->prepare("
            SELECT U.idUtente, U.nome, U.cognome
            FROM Utente U
        ");
        $sth->execute();

        return $sth->fetchAll();
    }

    private function createAssociation($data)
    {
        $nomeAssociazione = $data['nomeAssociazione'];
        $logo = $data['logo'];
        $membri = $data['membri'];

        $sth = $this->db->prepare("
            INSERT INTO Associazione (
              idAssociazione, nomeAssociazione, logo
            ) VALUES (
              NULL, :nomeAssociazione, :logo
            )
        ");
        $sth->bindParam(':nomeAssociazione', $nomeAssociazione, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);

        $good = $sth->execute();

        if (!$good)
            return false;

        $associationID = $this->getLastAssociationID();

        foreach ($membri as $membro) {
            $good = $this->addBelong($associationID, $membro);

            if (!$good) {
                return false;
            }
        }

        return true;
    }

    private function getLastAssociationID()
    {
        $sth = $this->db->prepare("
            SELECT A.idAssociazione
            FROM Associazione A
            ORDER BY A.idAssociazione DESC
            LIMIT 1
        ");
        $good = $sth->execute();

        if (!$good)
            return false;

        return (int)$sth->fetch()['idAssociazione'];
    }

    private function addBelong($associationID, $membro)
    {
        $sth = $this->db->prepare("
            INSERT INTO Appartiene (
                idUtente, idAssociazione
            ) VALUES (
                :idUtente, :idAssociazione
            )
        ");
        $sth->bindParam(':idUtente', $membro, \PDO::PARAM_INT);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function getAssociation($associationID)
    {
        $sth = $this->db->prepare("
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo
            FROM Associazione A
            WHERE A.idAssociazione = :idAssociazione
        ");
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetch();
    }

    private function getAssociationMembers($associationID)
    {
        $sth = $this->db->prepare("
            SELECT U.idUtente, U.nome, U.cognome
            FROM Appartiene AP
            JOIN Utente U
            USING (idUtente)
            WHERE AP.idAssociazione = :idAssociazione
        ");
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchAll();
    }

    private function getBelongsByAssociation($associationID)
    {
        $sth = $this->db->prepare("
            SELECT AP.idUtente, AP.idAssociazione
            FROM Appartiene AP
            WHERE AP.idAssociazione = :idAssociazione
        ");
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchAll();
    }

    private function updateAssociation($associationID, $data)
    {
        $associationName = $data['nomeAssociazione'];
        $logo = $data['logo'];
        $members = $data['membri'];

        $sth = $this->db->prepare("
            UPDATE Associazione A
            SET A.nomeAssociazione = :nomeAssociazione, A.logo = :logo
            WHERE A.idAssociazione = :idAssociazione
        ");
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

    private function deleteOldBelongs($associationID)
    {
        $sth = $this->db->prepare("
            DELETE
            FROM Appartiene
            WHERE idAssociazione = :idAssociazione
        ");
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function createBelongs($associationID, $members)
    {
        foreach ($members as $member => $memberID) {
            $sth = $this->db->prepare("
                INSERT INTO Appartiene (
                    idUtente, idAssociazione
                )
                VALUES (
                    :idUtente, :idAssociazione
                )
            ");
            $sth->bindParam(':idUtente', $memberID, \PDO::PARAM_INT);
            $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);
            $good = $sth->execute();

            if (!$good) {
                return false;
            }
        }

        return true;
    }

    private function deleteAssociation($associationID)
    {
        $good = $this->deleteFromBelongs($associationID);

        if (!$good) {
            return false;
        }

        $sth = $this->db->prepare("
            DELETE
            FROM Associazione
            WHERE idAssociazione = :idAssociazione
        ");
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function deleteFromBelongs($associationID)
    {
        $sth = $this->db->prepare("
            DELETE
            FROM Appartiene
            WHERE idAssociazione = :idAssociazione
        ");
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
    }
}