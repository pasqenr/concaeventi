<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
use http\Exception\InvalidArgumentException;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;

/**
 * @property Router router
 * @property \PDO db
 */
class EventController extends Controller
{
    private $IMAGE_PATH = WWW_PATH.'/img/events/';

    public function showEvents(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $events  = $this->getEvents();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/events.twig', [
            'utente' => $this->user,
            'eventi' => $events
        ]);
    }

    public function create(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::PUBLISHER);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associations = $this->getUserAssociations($this->user['idUtente']);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/create.twig', [
            'utente' => $this->user,
            'associazioni' => $associations
        ]);
    }

    public function doCreate(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createEvent($this->user['idUtente'], $parsedBody);

        if ($created === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    public function delete(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];

        try {
            $event = $this->getEvent($eventID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'evento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/delete.twig', [
            'utente' => $this->user,
            'evento' => $event
        ]);
    }

    public function doDelete(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];
        $deleted = $this->deleteEvent($eventID);

        if ($deleted === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    public function edit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::PUBLISHER);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];
        try {
            $event = $this->getEvent($eventID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'evento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        $associations = $this->getUserAssociations($this->user['idUtente']);

        try {
            $eventAssociations = $this->getEventAssociationsIds($event['nomeAssociazione']);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare le associazioni.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/edit.twig', [
            'utente' => $this->user,
            'evento' => $event,
            'associazioni' => $associations,
            'associazioniEvento' => $eventAssociations
        ]);
    }

    public function doEdit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::PUBLISHER);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $updated = $this->updateEvent($parsedBody);

        if ($updated === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    public function showPage(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth($response);
        $eventID = (int)$args['id'];

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        try {
            $event = $this->getEvent($eventID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'evento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        $associations = $this->getEventAssociations($event);

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/page.twig', [
            'utente' => $this->user,
            'evento' => $event,
            'associazioni' => $associations
        ]);
    }

    public function page(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];

        try {
            $event = $this->getEvent($eventID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'evento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }
        $associations = $this->getEventAssociations($event);

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/page.twig', [
            'utente' => $this->user,
            'evento' => $event,
            'associazioni' => $associations
        ]);
    }

    public function doPage(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $modified = $this->updatePageEvent($args['id'], $parsedBody, $uploadedFiles);

        if ($modified === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
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

        /** @noinspection ForeachInvariantsInspection */
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
     * @throws \PDOException
     */
    private function getEvents(): array
    {
        $sth = $this->db->query('
            SELECT U.idUtente, A.idAssociazione, E.idEvento, E.titolo, E.immagine, E.descrizione, E.istanteCreazione,
                   E.istanteInizio, E.istanteFine, E.pagina, E.revisionato, A2.nomeAssociazione AS nomeAssPrimaria,
                   A.nomeAssociazione, A.logo, U.nome AS nomeUtente, U.cognome AS cognomeUtente, U.email, U.ruolo
            FROM Evento E
            LEFT JOIN Proporre P
            USING (idEvento)
            LEFT JOIN Associazione A
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
            LEFT JOIN Associazione A2
            ON (E.idAssPrimaria = A2.idAssociazione)
            WHERE DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) > 0
            ORDER BY E.istanteInizio
        ');
        try {
            $events = $sth->fetchAll();
        } catch (\PDOException $e) {
            $this->setErrorMessage('getEvents(): PDOException, check errorInfo.',
                'Recupero eventi: errore nell\'elaborazione dei dati.',
                $this->db->errorInfo());

            throw $e;
        }

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
        $idUtente = $userID;
        $titolo = $data['titolo'];
        $immagine = $data['immagine'];
        $descrizione = $data['descrizione'];
        $istanteInizio = $data['istanteInizio'];
        $istanteFine = $data['istanteFine'];
        $revisionato = $data['revisionato'];
        $associazioni = $data['associazioni'];
        $idAssPrimaria = $data['assPrimaria'];

        $date_pattern = '^\d{4}-\d{2}-\d{2} (\d{2}(:\d{2}(:\d{2})?)?)?$^';

        if ($titolo === '' || $descrizione === '' || $istanteInizio === '' || $istanteFine === '' ||
            $associazioni === '' || $idAssPrimaria === '') {
            $this->setErrorMessage('createEvent(): Empty field.',
                'Creazione evento: un campo obbligatorio non è stato compilato.');

            return false;
        }

        if (!preg_match($date_pattern, $istanteInizio) || !preg_match($date_pattern, $istanteFine)) {
            $this->setErrorMessage('createEvent(): Wrong date match.',
                'Creazione evento: formato data errato.');

            return false;
        }

        if ($revisionato === 'on') {
            $revisionato = 1;
        } else {
            $revisionato = 0;
        }

        $this->db->beginTransaction();

        $sth = $this->db->prepare('
            INSERT INTO Evento (
                idEvento, titolo, immagine, descrizione, istanteCreazione, istanteInizio, istanteFine, 
                revisionato, idUtente, idAssPrimaria
            )
            VALUES (
                NULL, :titolo, :immagine, :descrizione, CURRENT_TIMESTAMP, :istanteInizio, :istanteFine, 
                :revisionato, :idUtente, :idAssPrimaria
            )
        ');
        $sth->bindParam(':titolo', $titolo, \PDO::PARAM_STR);
        $sth->bindParam(':immagine', $immagine, \PDO::PARAM_STR);
        $sth->bindParam(':descrizione', $descrizione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteInizio', $istanteInizio, \PDO::PARAM_STR);
        $sth->bindParam(':istanteFine', $istanteFine, \PDO::PARAM_STR);
        $sth->bindParam(':revisionato', $revisionato, \PDO::PARAM_INT);
        $sth->bindParam(':idUtente', $idUtente, \PDO::PARAM_INT);
        $sth->bindParam(':idAssPrimaria', $idAssPrimaria, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile creare l\'evento.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        try {
            $eventID = $this->getLastEventID();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile recupeare l\'ultimo evento.');

            $this->db->rollBack();

            return false;
        }

        /** @var $associazioni int[] */
        foreach ($associazioni as $ass) {
            try {
                $this->addPropose($eventID, $ass);
            } catch (\PDOException $e) {
                $this->setErrorMessage('PDOException, check errorInfo.',
                    'Impossibile associare le associazioni.');

                $this->db->rollBack();

                return false;
            }
        }
        $this->db->commit();

        return true;
    }

    private function getUserAssociations($userID): array
    {
        $sth = $this->db->prepare('
            SELECT A.idAssociazione, A.nomeAssociazione, A.logo
            FROM Associazione A
              JOIN Appartiene AP
              USING (idAssociazione)
            WHERE AP.idUtente = :idUtente
        ');
        $sth->bindParam(':idUtente', $userID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

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

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
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

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return true;
    }

    /**
     * Get the event identified by the ID.
     *
     * @param $eventID
     * @return array The events.
     * @throws \PDOException
     * @internal param int $id The event identifier.
     */
    private function getEvent($eventID): array
    {
        $id = (int)$eventID;

        $sth = $this->db->prepare('
            SELECT U.idUtente, A.idAssociazione, E.idEvento, E.titolo, E.immagine, E.descrizione, E.istanteCreazione,
                   E.istanteInizio, E.istanteFine, E.pagina, E.revisionato, A2.nomeAssociazione AS nomeAssPrimaria, 
                   A2.idAssociazione AS idAssPrimaria, A2.stile, A2.telefono, A.nomeAssociazione, A.logo, 
                   U.nome AS nomeUtente, U.cognome AS cognomeUtente, U.email, U.ruolo
            FROM Evento E
            LEFT JOIN Proporre P
            USING (idEvento)
            LEFT JOIN Associazione A
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
            LEFT JOIN Associazione A2
            ON (E.idAssPrimaria = A2.idAssociazione)
            WHERE E.idEvento = :eventID
        ');
        $sth->bindParam(':eventID', $id, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        $events = $sth->fetchAll();

        if (empty($events)) {
            return [];
        }

        $events = $this->mergeAssociations($events);

        return $events[0];
    }

    private function deleteEvent($eventID): bool
    {
        $this->db->beginTransaction();

        try {
            $this->deleteFromProposes($eventID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare le associazioni collegate all\'evento.');

            $this->db->rollBack();

            return false;
        }

        $sth = $this->db->prepare('
            DELETE
            FROM Evento
            WHERE idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare l\'evento.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        $this->db->commit();

        return true;
    }

    private function deleteFromProposes($eventID): bool
    {

        $sth = $this->db->prepare('
            DELETE
            FROM Proporre
            WHERE idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return true;
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
        $revisionato = $update['revisionato'];

        $date_pattern = '^\d{4}-\d{2}-\d{2} (\d{2}(:\d{2}(:\d{2})?)?)?$^';

        if ($titolo === '' || $descrizione === '' || $istanteInizio === '' || $istanteFine === '') {
            $this->setErrorMessage('updateEvent(): Empty field.',
                'Modifica evento: un campo obbligatorio non è stato compilato.');

            return false;
        }

        if (!preg_match($date_pattern, $istanteInizio) || !preg_match($date_pattern, $istanteFine)) {
            $this->setErrorMessage('updateEvent(): Wrong date match.',
                'Modifica evento: formato data errato.');

            return false;
        }

        if ($revisionato === 'on') {
            $revisionato = 1;
        } else {
            $revisionato = 0;
        }

        $this->db->beginTransaction();

        $sth = $this->db->prepare('
            UPDATE Evento E 
            SET E.titolo = :titolo, E.immagine = :immagine, E.descrizione = :descrizione, 
                E.istanteCreazione = :istanteCreazione, E.istanteInizio = :istanteInizio, E.istanteFine = :istanteFine,
                E.revisionato = :revisionato
            WHERE E.idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':titolo', $titolo, \PDO::PARAM_STR);
        $sth->bindParam(':immagine', $immagine, \PDO::PARAM_STR);
        $sth->bindParam(':descrizione', $descrizione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteCreazione', $istanteCreazione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteInizio', $istanteInizio, \PDO::PARAM_STR);
        $sth->bindParam(':istanteFine', $istanteFine, \PDO::PARAM_STR);
        $sth->bindParam(':revisionato', $revisionato, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare l\'evento.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        try {
            $this->deleteOldProposes($eventID);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare le precedenti associazioni collegate all\'evento.');

            $this->db->rollBack();

            return false;
        }

        try {
            $this->createProposes($eventID, $update);
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare le precedenti associazioni collegate all\'evento.');

            $this->db->rollBack();

            return false;
        }

        $this->db->commit();

        return true;
    }

    private function deleteOldProposes($eventID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Proporre
            WHERE idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return true;
    }

    private function createProposes($eventID, $event): bool
    {
        $associationsIds = $event['associazioni'];

        /** @var $associationsIds int[] */
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


            try {
                $sth->execute();
            } catch (\PDOException $e) {
                throw $e;
            }
        }

        return true;
    }

    private function getEventAssociationsIds($ass): array
    {
        $associationNames = explode(', ', $ass);
        $associations = [];
        $assCount = count($associationNames);

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $assCount; $i++) {
            try {
                $associationID = $this->getAssociationIdByName($associationNames[$i]);
            } catch (\PDOException $e) {
                throw $e;
            }

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

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }


        return $sth->fetch()['idAssociazione'];
    }

    private function getEventAssociations($event): array
    {
        $associationNames = explode(', ', $event['nomeAssociazione']);
        $associationLogos = explode(', ', $event['logo']);
        $associations = [];
        $assCount = count($associationNames);

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $assCount; $i++) {
            $associations[$i]['nome'] = $associationNames[$i];
            $associations[$i]['logo'] = $associationLogos[$i];
        }

        return $associations;
    }

    private function updatePageEvent($eventID, $update, $files): bool
    {
        $id = (int)$eventID;
        $page = $update['pagina'];
        $imageFilename = '';

        /**
         * @var $image \Slim\Http\UploadedFile
         */
        if (!empty($files)) {
            $image = $files['locandina'];

            if ($image->getError() === UPLOAD_ERR_OK) {
                $imageFilename = $image->getClientFilename();
            }

            if ($this->isValidImage($image) === false) {
            $this->setErrorMessage('Uploaded file is not an valid image.',
                'Il file caricato non è un\'immagine col formato supportato.',
                $this->db->errorInfo());

                return false;
            }
        }

        $sth = $this->db->prepare('
            UPDATE Evento E
            SET E.pagina = :pagina,
              E.immagine = :immagine
            WHERE E.idEvento = :eventID
        ');
        $sth->bindParam(':pagina', $page, \PDO::PARAM_STR);
        $sth->bindParam(':eventID', $id, \PDO::PARAM_INT);
        $sth->bindParam(':immagine', $imageFilename, \PDO::PARAM_STR);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare la pagina.',
                $this->db->errorInfo());

            return false;
        }

        /**
         * TODO: Make folder writable
         */
        if ($imageFilename !== '') {
            try {
                $image->moveTo($this->IMAGE_PATH.$imageFilename);
            } catch (InvalidArgumentException $e) {

            } catch (\Exception $e) {

            }
        }

        return true;
    }

    /**
     * @var $image \Slim\Http\UploadedFile
     * @return bool TRUE if the file have an image format, FALSE otherwise.
     *         WARNING: This function is NOT secure.
     */
    private function isValidImage($image): bool
    {
        return !(strrpos($image->getClientMediaType(), 'image') === false);
    }
}