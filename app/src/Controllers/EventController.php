<?php

namespace App\Controllers;

use \App\Exceptions\AuthException;
use \App\Helpers\SessionHelper;
use \App\Helpers\ErrorHelper;
use \App\Models\EventModel;
use \App\Models\AssociationModel;
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
    private $eventModel;
    private $associationModel;
    private $errorHelper;

    /**
     * EventController constructor.
     * @param \Slim\Container $container
     * @throws \InvalidArgumentException
     */
    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->eventModel = new EventModel($this->db, $this->errorHelper);
        $this->associationModel = new AssociationModel($this->db, $this->errorHelper);
    }

    /**
     * Show all the events even if not approved.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \PDOException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function showEvents(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $events = $this->getEvents($this->user['idUtente']);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/events.twig', [
            'utente' => $this->user,
            'eventi' => $events
        ]);
    }

    /**
     * Create page for a new event.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \PDOException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function create(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

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

    /**
     * Event creation action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    /**
     * Delete page for an event.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'evento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
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

    /**
     * Delete event action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    /**
     * Edit page for an event.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \PDOException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function edit(/** @noinspection PhpUnusedParameterInspection */
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
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'evento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        if (empty($event)) {
            return $response->withRedirect($this->router->pathFor('not-found'));
        }

        $associations = $this->getUserAssociations($this->user['idUtente']);

        try {
            $eventAssociations = $this->getEventAssociationsIds($event['nomeAssociazione']);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare le associazioni.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
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

    /**
     * Edit event action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function doEdit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $updated = $this->updateEvent($parsedBody);

        if ($updated === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    /**
     * Edit the event's page page.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'evento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
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

    /**
     * Edit the event page.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function doPage(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $modified = $this->updatePage($args['id'], $parsedBody, $uploadedFiles);

        if ($modified === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('events'));
    }

    /**
     * Return the event identified by $eventID.
     *
     * @param int $eventID The unique event identifier.
     * @return array The event.
     * @throws \PDOException
     */
    private function getEvent($eventID): array
    {
        return $this->eventModel->getEvent($eventID);
    }

    /**
     * Get all the events that are available before the current timestamp and ordered by timestamp.
     *
     * @param $userID int The current user identifier.
     * @return array The events that belongs
     */
    private function getEvents($userID): array
    {
        return $this->eventModel->getEvents($userID);
    }

    /**
     * Create a new event created by the user with $userID and with the $data array.
     *
     * @param int $userID The unique user identifier that created the event.
     * @param array $data The event fields.
     * @return bool TRUE if the events was created, FALSE if not. Errors are set internally.
     */
    private function createEvent($userID, $data): bool
    {
        if ($this->checkEventData($data) === false) {
            return false;
        }

        return $this->eventModel->createEvent($userID, $data);
    }

    /**
     * Return an array of the associations associated to the user $userID.
     *
     * @param int $userID The user unique identifier.
     * @return array The array of the associations.
     * @throws \PDOException
     */
    private function getUserAssociations($userID): array
    {
        return $this->associationModel->getUserAssociations($userID);
    }

    /**
     * Delete the event identified by $eventID.
     *
     * @param int $eventID The unique event identifier.
     * @return bool
     */
    private function deleteEvent($eventID): bool
    {
        return $this->eventModel->deleteEvent($eventID);
    }

    /**
     * Edit an event identified by $update['id'] and the data in $update.
     *
     * WARNING: This function is NOT secure. The user can send a different event ID.
     *
     * @param array $update The array with the changed fields.
     * @return bool TRUE if the event was updated, FALSE otherwise.
     */
    private function updateEvent($update): bool
    {
        if ($this->checkEventData($update) === false) {
            return false;
        }

        return $this->eventModel->updateEvent($update);
    }

    /**
     * Take a string of associations separated by comma and returns an array of the associations IDs.
     * This function is expensive because for every association a query is executed.
     *
     * @param string $ass A string of valid association names separated by comma.
     * @return array An array of associations IDs.
     * @throws \PDOException
     */
    private function getEventAssociationsIds($ass): array
    {
        $associationNames = explode(', ', $ass);
        $associations = [];
        $assCount = \count($associationNames);

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

    /**
     * Return an association ID given a valid association name $assName.
     *
     * @param string $assName A valid association name.
     * @return string The associated association ID as string.
     * @throws \PDOException
     */
    private function getAssociationIdByName($assName): string
    {
        return $this->associationModel->getAssociationIdByName($assName);
    }

    /**
     * Return the associations of the event $event.
     *
     * @param array $event The event array, at list with 'nomeAssociazione' and 'logo' fields.
     * @return array An array with the event associations and fields 'nome' and 'logo'.
     */
    private function getEventAssociations($event): array
    {
        $associationNames = explode(', ', $event['nomeAssociazione']);
        $associationLogos = explode(', ', $event['logo']);
        $associations = [];
        $assCount = \count($associationNames);

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $assCount; $i++) {
            $associations[$i]['nome'] = $associationNames[$i];
            $associations[$i]['logo'] = $associationLogos[$i];
        }

        return $associations;
    }

    /**
     * Update a page of the event identified by $eventID with $update data. The $files are stored
     * internally.
     *
     * @param int $eventID The unique event identifier.
     * @param array $update The array with the new fields data.
     * @param array $files The files uploaded.
     * @return bool TRUE if the event was changed, FALSE otherwise. Errors are set internally.
     */
    private function updatePage($eventID, $update, $files): bool
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
            $this->errorHelper->setErrorMessage('Uploaded file is not an valid image.',
                'Il file caricato non è un\'immagine col formato supportato.',
                $this->db->errorInfo());

                return false;
            }
        }

        $data['pagina'] = $page;
        $data['eventID'] = $id;
        $data['immagine'] = $imageFilename;

        $good = $this->eventModel->updatePage($eventID, $data);

        if ($good === false) {
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
     * Check if $image is a valid image file.
     *
     * WARNING: This function is NOT secure.
     *
     * @var $image \Slim\Http\UploadedFile
     * @return bool TRUE if the file have an image format, FALSE otherwise.
     */
    private function isValidImage($image): bool
    {
        return !(strrpos($image->getClientMediaType(), 'image') === false);
    }

    /**
     * Check the parameters of create or edit event.
     *
     * @param $data
     * @return bool TRUE if the tests pass, FALSE otherwise. Error message is also set.
     */
    private function checkEventData(&$data): bool
    {
        $titolo = $data['titolo'];
        $descrizione = $data['descrizione'];
        $associazioni = $data['associazioni'];
        $idAssPrimaria = $data['assPrimaria'];
        $approvato = $data['revisionato'] ?? null;

        $this->adjustDateTimeFormat($data);
        $istanteInizio = $data['istanteInizio'];
        $istanteFine = $data['istanteFine'];

        if ($titolo === '' || $descrizione === '' || $istanteInizio === '' || $istanteFine === '' ||
            $associazioni === '' || $idAssPrimaria === '') {
            $this->errorHelper->setErrorMessage('Empty field.',
                'Un campo obbligatorio non è stato compilato.');

            return false;
        }

        if (!$this->isValidDate($istanteInizio) || !$this->isValidDate($istanteFine)) {
            $this->errorHelper->setErrorMessage('Wrong date match.',
                'Formato data errato.');

            return false;
        }

        /*$istanteInizio = $this->addTimeZeros($istanteInizio);
        $istanteFine = $this->addTimeZeros($istanteFine);

        // Fix also the the values in $data
        $data['istanteInizio'] = $istanteInizio;
        $data['istanteFine'] = $istanteFine;*/

        $initDate = new \DateTimeImmutable($istanteInizio);
        $finishDate = new \DateTimeImmutable($istanteFine);

        if ($initDate > $finishDate) {
            $this->errorHelper->setErrorMessage(
                'Strarting date greater than finish date.',
                'Orario d\'inizio viene dopo quello di fine.');

            return false;
        }

        try {
            $data['revisionato'] = $this->changeApproval($approvato);
        } catch (AuthException $e) {
            $this->errorHelper->setErrorMessage(
                'Can\'t change approvation because of user authorization.',
                'Non disponi dei permessi necessari per cambiare l\'approvazione dell\'evento.'
            );

            $data['revisionato'] = 'off';

            return false;
        }

        return true;
    }

    /**
     * Change the approve of an event. If the user is not a PUBLISHER then a exception is throw.
     *
     * @param string $approved If the parameter is null or empty then the approve is set to 'off'.
     *               Otherwise the parameter is returned unchanged.
     * @return string 'off' if the parameter was null or an empty string, otherwise unchanged.
     * @throws \App\Exceptions\AuthException If the user is not a PUBLISHER the exception is thrown.
     */
    private function changeApproval($approved): string
    {
        if ($approved === null || $approved === '') {
            return 'off';
        }

        if ($this->session->auth(SessionHelper::PUBLISHER) === false) {
            throw new AuthException('The user doesn\'t have the permission level to do the action.');
        }

        return $approved;
    }

    private function isValidDate($date, $format = 'Y-m-d H:i:s'): bool
    {
        $d = \DateTimeImmutable::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    /**
     * Take a reference to $data parameters and modify it adding two new
     * columns: istanteInizio and istanteFine. The new columns are created
     * using the data and time parameters given by the user.
     *
     * @param $data array A reference to the input data
     */
    private function adjustDateTimeFormat(&$data): void
    {
        $data['istanteInizio'] = $this->joinDataTimeFormat(
            $data['giornoInizio'],
            $data['meseInizio'],
            $data['annoInizio'],
            $data['oraInizio'],
            $data['minutoInizio']
            );

        $data['istanteFine'] = $this->joinDataTimeFormat(
            $data['giornoFine'],
            $data['meseFine'],
            $data['annoFine'],
            $data['oraFine'],
            $data['minutoFine']
        );
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     *
     * Return a string representation of the date and time in the format
     * "Y-m-d H:i:s".
     *
     * @param $day string The day.
     * @param $month string The month.
     * @param $year string The year.
     * @param $hour string The hour.
     * @param $minute string The minute.
     * @return string A string in the format "Y-m-d H:i:s".
     */
    private function joinDataTimeFormat($day,
                                        $month,
                                        $year,
                                        $hour,
                                        $minute): string
    {
        return sprintf('%s-%02s-%02s %02s:%02s:00',
            $year,
            $month,
            $day,
            $hour,
            $minute);
    }
}