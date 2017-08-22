<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class FrontController extends Controller
{
    public function home(RequestInterface $request, ResponseInterface $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::ALL);
        $events = $this->getEvents();

        return $this->render($response, 'front/home.twig', [
            'utente' => $user,
            'eventi' => $events
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
            if ($current['idEvento'] === $event['idEvento'] && $current['nomeAssociazione'] !== $event['nomeAssociazione']) {
                $event['nomeAssociazione'] .= ', ' . $current['nomeAssociazione'];
                array_pop($eventsWithAssociations);
            }

            array_push($eventsWithAssociations, $event);

            $current = $event;
        }

        return $eventsWithAssociations;
    }

    /**
     * Get all the events that are available before the current timestamp and order them by timestamp.
     *
     * @return array The events.
     */
    private function getEvents()
    {
        $sth = $this->db->prepare("
            SELECT *
            FROM Evento E
            JOIN Proporre P
            USING (idEvento)
            JOIN Associazione A
            USING (idAssociazione)
            WHERE DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) > 0
            AND E.revisionato = TRUE
            ORDER BY E.istanteInizio
        ");
        $sth->execute();
        $events = $sth->fetchAll();

        $events = $this->mergeAssociations($events);

        return $events;
    }

    private function auth($response)
    {
        $session = new \RKA\Session();
        $user = [];

        if (SessionHelper::isLogged($session)) {
            SessionHelper::setSessionArray($session, $user);
        }

        return $user;
    }
}
