<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class PageController extends Controller
{
    public function showPage(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::ALL);
        $eventID = (int)$args['id'];
        $event   = $this->getEvent($eventID);
        $associations = $this->getEventAssociations($event);

        return $this->render($response, 'front/page.twig', [
            'utente' => $user,
            'evento' => $event,
            'associazioni' => $associations
        ]);
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

        $eventsWithAssociations = [];
        $current = $events[0];

        foreach ($events as &$event) {
            if ($current['nomeAssociazione'] !== $event['nomeAssociazione']) {
                $event['nomeAssociazione'] .= ', ' . $current['nomeAssociazione'];
                $event['logo'] .= ', ' . $current['logo'];
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
            JOIN Associazione A
            USING (idAssociazione)
            JOIN Utente U
            USING (idUtente)
            WHERE E.idEvento = :eventID
        ");
        $sth->bindParam(':eventID', $id, \PDO::PARAM_INT);
        $sth->execute();
        $events = $sth->fetchAll();

        $events = $this->mergeAssociations($events);

        return $events;
    }

    private function getEventAssociations($event)
    {
        $associationNames = explode(', ', $event['nomeAssociazione']);
        $associationLogos = explode(', ', $event['logo']);
        $associations = [];

        for ($i = 0; $i < count($associationNames); $i++) {
            $associations[$i]['nome'] = $associationNames[$i];
            $associations[$i]['logo'] = $associationLogos[$i];
        }

        return $associations;
    }
}