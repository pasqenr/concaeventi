<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class PageEventController extends Controller
{
    public function page(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::EDITORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('events'));
        }

        $eventID = (int)$args['id'];
        $event   = $this->getEvent($eventID);
        $associations = $this->getEventAssociations($event);

        return $this->render($response, 'events/page.twig', [
            'utente' => $user,
            'evento' => $event,
            'associazioni' => $associations
        ]);
    }

    public function doPage(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::EDITORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('events'));
        }

        $parsedBody = $request->getParsedBody();
        $modified = $this->updateEvent($args['id'], $parsedBody);

        if ($modified === true) {
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

        $eventsWithAssociations = [];
        $old = $events[0];

        foreach ($events as $event) {
            if ($old['nomeAssociazione'] !== $event['nomeAssociazione']) {
                $event['nomeAssociazione'] .= ', ' . $old['nomeAssociazione'];
                $event['logo'] .= ', ' . $old['logo'];
                array_pop($eventsWithAssociations);
            }

            array_push($eventsWithAssociations, $event);

            $old = $event;
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
                   U.nome AS nomeUtente, U.cognome AS cognomeUtente
            FROM Evento E
            JOIN Proporre P
            USING (idEvento)
            JOIN Associazione A
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

    private function updateEvent($eventID, $update)
    {
        $id = (int)$eventID;
        $pagina = $update['pagina'];

        $sth = $this->db->prepare("
            UPDATE Evento E
            SET E.pagina = :pagina
            WHERE E.idEvento = :eventID
        ");
        $sth->bindParam(':pagina', $pagina, \PDO::PARAM_STR);
        $sth->bindParam(':eventID', $id, \PDO::PARAM_INT);

        return $sth->execute();
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