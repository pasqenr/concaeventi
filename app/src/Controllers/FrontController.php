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
class FrontController extends Controller
{
    public function home(Request $request, Response $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::ALL);
        $events = $this->getEvents();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
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
    private function mergeAssociations($events): array
    {
        if (empty($events)) {
            return [];
        }

        $eventsWithAssociations = [];
        $old = $events[0];

        $eventsCount = count($events);

        for ($i = 0, $j = 0; $i < $eventsCount; $i++, $j++) {
            if ($old['idEvento'] === $events[$i]['idEvento'] &&
                $old['nomeAssociazione'] !== $events[$i]['nomeAssociazione']) {
                $events[$i]['nomeAssociazione'] .= ', ' . $old['nomeAssociazione'];

                if ($j !== 0) {
                    $j--;
                }
            }

            $eventsWithAssociations[$j] = $events[$i];

            $old = $events[$i];
        }

        /*foreach ($events as &$event) {
            if ($current['idEvento'] === $event['idEvento'] && $current['nomeAssociazione'] !== $event['nomeAssociazione']) {
                $event['nomeAssociazione'] .= ', ' . $current['nomeAssociazione'];
                array_pop($eventsWithAssociations);
            }

            array_push($eventsWithAssociations, $event);

            $current = $event;
        }*/

        return $eventsWithAssociations;
    }

    /**
     * Get all the events that are available before the current timestamp and order them by timestamp.
     *
     * @return array The events.
     */
    private function getEvents(): array
    {
        $sth = $this->db->query('
            SELECT *
            FROM Evento E
            JOIN Proporre P
            USING (idEvento)
            JOIN Associazione A
            USING (idAssociazione)
            WHERE DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) > 0
            AND E.revisionato = TRUE
            ORDER BY E.istanteInizio
        ');
        $events = $sth->fetchAll();

        $events = $this->mergeAssociations($events);

        return $events;
    }
}
