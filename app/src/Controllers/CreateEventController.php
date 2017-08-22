<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class CreateEventController extends Controller
{
    public function create(RequestInterface $request, ResponseInterface $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $associations = $this->getAssociations();

        return $this->render($response, 'events/create.twig', [
            'utente' => $user,
            'associazioni' => $associations
        ]);
    }

    public function doCreate(RequestInterface $request, ResponseInterface $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::EDITORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createEvent($user['idUtente'], $parsedBody);

        if ($created === true) {
            return $response->withRedirect($this->router->pathFor('events'));
        } else {
            return $response->withRedirect($this->router->pathFor('error'));
        }
    }
    
    private function createEvent($userID, $data)
    {
        $id = $userID;
        $titolo = $data['titolo'];
        $immagine = $data['immagine'];
        $descrizione = $data['descrizione'];
        $istanteInizio = $data['istanteInizio'];
        $istanteFine = $data['istanteFine'];
        $pagina = $data['pagina'];
        $revisionato = $data['revisionato'];
        $associazioni = $data['associazioni'];

        if ($titolo === '' || $descrizione === '' || $istanteInizio === 'yyyy-mm-gg hh:mm:ss' ||
            $istanteFine === 'yyyy-mm-gg hh:mm:ss') {
            return false;
        }

        if ($revisionato === "on") {
            $revisionato = 1;
        } else {
            $revisionato = 0;
        }

        $sth = $this->db->prepare("
            INSERT INTO Evento (
                idEvento, titolo, immagine, descrizione, istanteCreazione, istanteInizio, istanteFine, 
                pagina, revisionato, idUtente
            )
            VALUES (
                NULL, :titolo, :immagine, :descrizione, CURRENT_TIMESTAMP, :istanteInizio, :istanteFine, :pagina, 
                :revisionato, :idUtente
            )
        ");
        $sth->bindParam(':titolo', $titolo, \PDO::PARAM_STR);
        $sth->bindParam(':immagine', $immagine, \PDO::PARAM_STR);
        $sth->bindParam(':descrizione', $descrizione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteInizio', $istanteInizio, \PDO::PARAM_STR);
        $sth->bindParam(':istanteFine', $istanteFine, \PDO::PARAM_STR);
        $sth->bindParam(':pagina', $pagina, \PDO::PARAM_STR);
        $sth->bindParam(':revisionato', $revisionato, \PDO::PARAM_INT);
        $sth->bindParam(':idUtente', $id, \PDO::PARAM_INT);

        $good = $sth->execute();

        if (!$good)
            return false;

        $eventID = $this->getLastEventID();

        foreach ($associazioni as $ass) {
            $good = $this->addPropose($eventID, $ass);

            if (!$good) {
                return false;
            }
        }

        return true;
    }

    private function getAssociations()
    {
        $sth = $this->db->prepare("
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo
            FROM Associazione A
        ");
        $sth->execute();

        return $sth->fetchAll();
    }

    private function getLastEventID()
    {
        $sth = $this->db->prepare("
            SELECT E.idEvento
            FROM Evento E
            ORDER BY E.idEvento DESC
            LIMIT 1
        ");
        $good = $sth->execute();

        if (!$good)
            return -1;

        return (int)$sth->fetch()['idEvento'];
    }

    private function addPropose($eventID, $associationID)
    {
        $sth = $this->db->prepare("
            INSERT INTO Proporre (
                idEvento, idAssociazione
            )
            VALUES (
                :idEvento, :idAssociazione
            )
        ");
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
    }
}