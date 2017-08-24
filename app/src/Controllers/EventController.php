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
class EventController extends Controller
{
    public function showEvents(Request $request, Response $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::EDITORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $events  = $this->getEvents();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/events.twig', [
            'utente' => $user,
            'eventi' => $events
        ]);
    }

    public function create(Request $request, Response $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associations = $this->getAssociations();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/create.twig', [
            'utente' => $user,
            'associazioni' => $associations
        ]);
    }

    public function doCreate(Request $request, Response $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::EDITORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createEvent($user['idUtente'], $parsedBody);

        if ($created === false) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    public function delete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];
        $event = $this->getEvent($eventID);

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/delete.twig', [
            'utente' => $user,
            'evento' => $event
        ]);
    }

    public function doDelete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];
        $this->deleteEvent($eventID);

        return $response->withRedirect($this->router->pathFor('events'));
    }

    public function edit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];
        $event   = $this->getEvent($eventID);

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        $associations = $this->getAssociations();

        $eventAssociations = $this->getEventAssociationsIds($event['nomeAssociazione']);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/edit.twig', [
            'utente' => $user,
            'evento' => $event,
            'associazioni' => $associations,
            'associazioniEvento' => $eventAssociations
        ]);
    }

    public function doEdit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::PUBLISHER);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $updated = $this->updateEvent($parsedBody);

        if ($updated === false) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    public function showPage(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::ALL);
        $eventID = (int)$args['id'];
        $event   = $this->getEvent($eventID);
        $associations = $this->getEventAssociations($event);

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/page.twig', [
            'utente' => $user,
            'evento' => $event,
            'associazioni' => $associations
        ]);
    }

    public function page(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::EDITORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('events'));
        }

        $eventID = (int)$args['id'];
        $event   = $this->getEvent($eventID);
        $associations = $this->getEventAssociations($event);

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/page.twig', [
            'utente' => $user,
            'evento' => $event,
            'associazioni' => $associations
        ]);
    }

    public function doPage(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::EDITORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $modified = $this->updatePageEvent($args['id'], $parsedBody);

        if ($modified === false) {
            return $response->withRedirect($this->router->pathFor('error'));

        }

        return $response->withRedirect($this->router->pathFor('events'));
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
                $events[$i]['logo'] .= ', ' . $old['logo'];

                if ($j !== 0) {
                    $j--;
                }
            }

            $eventsWithAssociations[$j] = $events[$i];

            $old = $events[$i];
        }

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
            WHERE DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) > 0
            ORDER BY E.istanteInizio
        ');
        $events = $sth->fetchAll();

        $events = $this->mergeAssociations($events);

        return $events;
    }

    /**
     * @param $userID
     * @param $data
     * @return bool
     */
    private function createEvent($userID, $data): bool
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

        if ($revisionato === 'on') {
            $revisionato = 1;
        } else {
            $revisionato = 0;
        }

        $sth = $this->db->prepare('
            INSERT INTO Evento (
                idEvento, titolo, immagine, descrizione, istanteCreazione, istanteInizio, istanteFine, 
                pagina, revisionato, idUtente
            )
            VALUES (
                NULL, :titolo, :immagine, :descrizione, CURRENT_TIMESTAMP, :istanteInizio, :istanteFine, :pagina, 
                :revisionato, :idUtente
            )
        ');
        $sth->bindParam(':titolo', $titolo, \PDO::PARAM_STR);
        $sth->bindParam(':immagine', $immagine, \PDO::PARAM_STR);
        $sth->bindParam(':descrizione', $descrizione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteInizio', $istanteInizio, \PDO::PARAM_STR);
        $sth->bindParam(':istanteFine', $istanteFine, \PDO::PARAM_STR);
        $sth->bindParam(':pagina', $pagina, \PDO::PARAM_STR);
        $sth->bindParam(':revisionato', $revisionato, \PDO::PARAM_INT);
        $sth->bindParam(':idUtente', $id, \PDO::PARAM_INT);

        $good = $sth->execute();

        if (!$good) {
            return false;
        }

        $eventID = $this->getLastEventID();

        foreach ($associazioni as $ass) {
            $good = $this->addPropose($eventID, $ass);

            if (!$good) {
                return false;
            }
        }

        return true;
    }

    private function getAssociations(): array
    {
        $sth = $this->db->query('
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo
            FROM Associazione A
        ');

        return $sth->fetchAll();
    }

    private function getLastEventID(): int
    {
        $sth = $this->db->prepare('
            SELECT E.idEvento
            FROM Evento E
            ORDER BY E.idEvento DESC
            LIMIT 1
        ');
        $good = $sth->execute();

        if (!$good) {
            return -1;
        }

        return (int)$sth->fetch()['idEvento'];
    }

    private function addPropose($eventID, $associationID): bool
    {
        $sth = $this->db->prepare('
            INSERT INTO Proporre (
                idEvento, idAssociazione
            )
            VALUES (
                :idEvento, :idAssociazione
            )
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    /**
     * Get the event identified by the ID.
     *
     * @param $eventID
     * @return array The events.
     * @internal param int $id The event identifier.
     */
    private function getEvent($eventID): array
    {
        $id = (int)$eventID;

        $sth = $this->db->prepare('
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
        ');
        $sth->bindParam(':eventID', $id, \PDO::PARAM_INT);
        $sth->execute();
        $events = $sth->fetchAll();

        if (empty($events)) {
            return [];
        }

        $events = $this->mergeAssociations($events);

        return $events[0];
    }

    private function deleteEvent($eventID): bool
    {
        $good = $this->deleteFromProposes($eventID);

        if (!$good) {
            return false;
        }

        $sth = $this->db->prepare('
            DELETE
            FROM Evento
            WHERE idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function deleteFromProposes($eventID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Proporre
            WHERE idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function updateEvent($update): bool
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

        if ($revisionato === 'on') {
            $revisionato = 1;
        } else {
            $revisionato = 0;
        }

        $sth = $this->db->prepare('
            UPDATE Evento E 
            SET E.titolo = :titolo, E.immagine = :immagine, E.descrizione = :descrizione, 
                E.istanteCreazione = :istanteCreazione, E.istanteInizio = :istanteInizio, E.istanteFine = :istanteFine,
                E.pagina = :pagina, E.revisionato = :revisionato
            WHERE E.idEvento = :idEvento
        ');
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

    private function deleteOldProposes($eventID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Proporre
            WHERE idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function createProposes($eventID, $event): bool
    {
        $associationsIds = $event['associazioni'];

        foreach ($associationsIds as $associationsID) {
            $sth = $this->db->prepare('
                INSERT INTO Proporre (
                    idEvento, idAssociazione
                )
                VALUES (
                    :idEvento, :idAssociazione
                )
            ');
            $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
            $sth->bindParam(':idAssociazione', $associationsID, \PDO::PARAM_INT);
            $good = $sth->execute();

            if (!$good) {
                return false;
            }
        }

        return true;
    }

    private function getEventAssociationsIds($ass): array
    {
        $associationNames = explode(', ', $ass);
        $associations = [];
        $assCount = count($associationNames);

        for ($i = 0; $i < $assCount; $i++) {
            $associationID = $this->getAssociationIdByName($associationNames[$i]);
            $associations[$i] = $associationID;
        }

        return $associations;
    }

    private function getAssociationIdByName($assName): string
    {
        $sth = $this->db->prepare('
            SELECT A.idAssociazione
            FROM Associazione A
            WHERE A.nomeAssociazione LIKE :nomeAssociazione
            LIMIT 1
        ');
        $sth->bindParam(':nomeAssociazione', $assName, \PDO::PARAM_STR);
        $sth->execute();

        return $sth->fetch()['idAssociazione'];
    }

    private function getEventAssociations($event): array
    {
        $associationNames = explode(', ', $event['nomeAssociazione']);
        $associationLogos = explode(', ', $event['logo']);
        $associations = [];
        $assCount = count($associationNames);

        for ($i = 0; $i < $assCount; $i++) {
            $associations[$i]['nome'] = $associationNames[$i];
            $associations[$i]['logo'] = $associationLogos[$i];
        }

        return $associations;
    }

    private function updatePageEvent($eventID, $update): bool
    {
        $id = (int)$eventID;
        $pagina = $update['pagina'];

        $sth = $this->db->prepare('
            UPDATE Evento E
            SET E.pagina = :pagina
            WHERE E.idEvento = :eventID
        ');
        $sth->bindParam(':pagina', $pagina, \PDO::PARAM_STR);
        $sth->bindParam(':eventID', $id, \PDO::PARAM_INT);

        return $sth->execute();
    }
}