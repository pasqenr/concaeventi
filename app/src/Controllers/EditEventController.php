<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class EditEventController extends Controller
{
    public function edit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $eventID = (int)$args['id'];
        $event   = $this->getEvent($eventID);
        $associations = $this->getAssociations();

        $eventAssociations = $this->getEventAssociationsIds($event['nomeAssociazione']);

        return $this->render($response, 'events/edit.twig', [
            'utente' => $user,
            'evento' => $event,
            'associazioni' => $associations,
            'associazioniEvento' => $eventAssociations
        ]);
    }

    public function doEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);;

        $parsedBody = $request->getParsedBody();
        $updated = $this->updateEvent($parsedBody);

        if ($updated === true) {
            return $response->withRedirect($this->router->pathFor('events'));
        } else {
            return $response->withRedirect($this->router->pathFor('error'));
        }
    }

    /**
     * Merge the Associtations on the rows with the same idEvento. The separator used is comma and space (', ').
     * If there aren't events the function returns an empty array.
     *
     * @param $events array The events fetched from the database.
     * @return array  The events merged with the Associtations in the same Event. If the array is empty the function
     *                return an empty array.
     */
    private function mergeAssociations($events)
    {
        if (empty($events))
            return [];

        /*if ($this->idxCount($events, 17) < 2)
            return $events[0];*/

        $eventsWithAssociations = [];
        $current = $events[0];

        foreach ($events as &$event) {
            if ($current['nomeAssociazione'] !== $event['nomeAssociazione']) {
                $event['nomeAssociazione'] .= ', ' . $current['nomeAssociazione'];
                array_pop($eventsWithAssociations);
            }

            array_push($eventsWithAssociations, $event);

            $current = $event;
        }

        return $eventsWithAssociations[0];
    }

    /**
     * Get the event identified by the ID.
     *
     * @param $id int The event identifier.
     * @return array The events.
     */
    private function getEvent($eventID)
    {
        $id = (int)$eventID;

        $sth = $this->db->prepare("
            SELECT U.idUtente, A.idAssociazione, E.idEvento, E.titolo, E.immagine, E.descrizione, E.istanteCreazione,
                   E.istanteInizio, E.istanteFine, E.pagina, E.revisionato, A.nomeAssociazione, A.logo,
                   U.nome AS nomeUtente, U.cognome AS cognomeUtente, U.email, U.ruolo
            FROM Evento E
            LEFT JOIN Proporre P
            USING (idEvento)
            LEFT JOIN Associazione A
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
            WHERE E.idEvento = :eventID
        ");
        $sth->bindParam(':eventID', $id, \PDO::PARAM_INT);
        $sth->execute();
        $events = $sth->fetchAll();

        $events = $this->mergeAssociations($events);

        return $events;
    }

    private function updateEvent($update)
    {
        $eventID = $update['id'];
        $titolo = $update['titolo'];
        $immagine = $update['immagine'];
        $descrizione = $update['descrizione'];
        $istanteCreazione = $update['istanteCreazione'];
        $istanteInizio = $update['istanteInizio'];
        $istanteFine = $update['istanteFine'];
        $pagina = $update['pagina'];
        $revisionato = $update['revisionato'];

        if ($revisionato === "on") {
            $revisionato = 1;
        } else {
            $revisionato = 0;
        }

        $sth = $this->db->prepare("
            UPDATE Evento E 
            SET E.titolo = :titolo, E.immagine = :immagine, E.descrizione = :descrizione, 
                E.istanteCreazione = :istanteCreazione, E.istanteInizio = :istanteInizio, E.istanteFine = :istanteFine,
                E.pagina = :pagina, E.revisionato = :revisionato
            WHERE E.idEvento = :idEvento
        ");
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':titolo', $titolo, \PDO::PARAM_STR);
        $sth->bindParam(':immagine', $immagine, \PDO::PARAM_STR);
        $sth->bindParam(':descrizione', $descrizione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteCreazione', $istanteCreazione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteInizio', $istanteInizio, \PDO::PARAM_STR);
        $sth->bindParam(':istanteFine', $istanteFine, \PDO::PARAM_STR);
        $sth->bindParam(':pagina', $pagina, \PDO::PARAM_STR);
        $sth->bindParam(':revisionato', $revisionato, \PDO::PARAM_INT);

        $good = $sth->execute();

        if (!$good) {
            return false;
        }

        $good = $this->deleteOldProposes($eventID);

        if (!$good) {
            return false;
        }

        $good = $this->createProposes($eventID, $update);

        return $good;
    }

    private function idxCount(&$array, $num_fields)
    {
        return count($array)/$num_fields;
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

    private function deleteOldProposes($eventID)
    {
        $sth = $this->db->prepare("
            DELETE
            FROM Proporre
            WHERE idEvento = :idEvento
        ");
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function createProposes($eventID, $event)
    {
        $associationsIds = $event['associazioni'];

        foreach ($associationsIds as $associationsID) {
            $sth = $this->db->prepare("
                INSERT INTO Proporre (
                    idEvento, idAssociazione
                )
                VALUES (
                    :idEvento, :idAssociazione
                )
            ");
            $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
            $sth->bindParam(':idAssociazione', $associationsID, \PDO::PARAM_INT);
            $good = $sth->execute();

            if (!$good) {
                return false;
            }
        }

        return true;
    }

    private function getEventAssociationsIds($ass)
    {
        $associationNames = explode(', ', $ass);
        $associations = [];

        foreach ($associationNames as $assName) {
            $associationID = $this->getAssociationIdByName($assName);
            array_push($associations, $associationID);
        }

        return $associations;
    }

    private function getAssociationIdByName($assName)
    {
        $sth = $this->db->prepare("
            SELECT A.idAssociazione
            FROM Associazione A
            WHERE A.nomeAssociazione LIKE :nomeAssociazione
            LIMIT 1
        ");
        $sth->bindParam(':nomeAssociazione', $assName, \PDO::PARAM_STR);
        $sth->execute();

        return $sth->fetch()['idAssociazione'];
    }
}