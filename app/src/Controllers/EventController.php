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

    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->eventModel = new EventModel($this->db, $this->errorHelper);
        $this->associationModel = new AssociationModel($this->db, $this->errorHelper);
    }

    public function showEvents(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $events = $this->getEvents();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'events/events.twig', [
            'utente' => $this->user,
            'eventi' => $events
        ]);
    }

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

    public function showPage(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
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

    private function getEvent($eventID): array
    {
        return $this->eventModel->getEvent($eventID);
    }

    private function getEvents(): array
    {
        return $this->eventModel->getEvents();
    }

    private function createEvent($userID, $data): bool
    {
        if ($this->checkEventData($data) === false) {
            return false;
        }

        return $this->eventModel->createEvent($userID, $data);
    }

    private function getUserAssociations($userID): array
    {
        return $this->associationModel->getUserAssociations($userID);
    }

    private function deleteEvent($eventID): bool
    {
        return $this->eventModel->deleteEvent($eventID);
    }

    private function updateEvent($update): bool
    {
        if ($this->checkEventData($update) === false) {
            return false;
        }

        return $this->eventModel->updateEvent($update);
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
        return $this->associationModel->getAssociationIdByName($assName);
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
     * @var $image \Slim\Http\UploadedFile
     * @return bool TRUE if the file have an image format, FALSE otherwise.
     *         WARNING: This function is NOT secure.
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
        $istanteInizio = $data['istanteInizio'];
        $istanteFine = $data['istanteFine'];
        $associazioni = $data['associazioni'];
        $idAssPrimaria = $data['assPrimaria'];
        $approvato = $data['revisionato'] ?? null;

        $date_pattern = '/^\d{4}-\d{2}-\d{2} (\d{2}(:\d{2}(:\d{2})?)?)?$/';

        if ($titolo === '' || $descrizione === '' || $istanteInizio === '' || $istanteFine === '' ||
            $associazioni === '' || $idAssPrimaria === '') {
            $this->errorHelper->setErrorMessage('checkEventData(): Empty field.',
                'Un campo obbligatorio non è stato compilato.');

            return false;
        }

        if (!preg_match($date_pattern, $istanteInizio) || !preg_match($date_pattern, $istanteFine)) {
            $this->errorHelper->setErrorMessage('checkEventData(): Wrong date match.',
                'Formato data errato.');

            return false;
        }

        $istanteInizio = $this->addTimeZeros($istanteInizio);
        $istanteFine = $this->addTimeZeros($istanteFine);

        $initDate = new \DateTime($istanteInizio);
        $finishDate = new \DateTime($istanteFine);

        if ($initDate > $finishDate) {
            $this->errorHelper->setErrorMessage(
                'checkEventData(): Strarting date greater than finish date.',
                'Orario d\'inizio viene dopo quello di fine.');

            return false;
        }

        try {
            $data['revisionato'] = $this->changeApproval($approvato);
        } catch (AuthException $e) {
            $this->errorHelper->setErrorMessage(
                'checkEventData(): Can\'t change approvation because of user authorization.',
                'Non disponi dei permessi necessari per cambiare l\'approvazione dell\'evento.'
            );

            $data['revisionato'] = 'off';

            return false;
        }

        return true;
    }

    private function addTimeZeros(&$datetime): string
    {
        if (strpos($datetime, ':') === false) {
            $datetime .= ':00';
        }

        if (strpos($datetime, ':', 16) === false) {
            $datetime .= ':00';
        }

        return $datetime;
    }

    /**
     * @param $approved
     * @return string
     * @throws \App\Exceptions\AuthException
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
}