<?php

namespace App\Controllers;

use \App\Helpers\ErrorHelper;
use \App\Models\EventModel;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;

/**
 * @property Router router
 * @property \PDO db
 */
class FrontController extends Controller
{
    private $eventModel;
    private $errorHelper;

    /**
     * FrontController constructor.
     * @param \Slim\Container $container
     * @throws \InvalidArgumentException
     */
    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->eventModel = new EventModel($this->db, $this->errorHelper);
    }

    /**
     * The home page with only the upcoming reviewed events.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @throws \PDOException
     */
    public function home(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $events = $this->getReviewedEvents();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/home.twig', [
            'utente' => $this->user,
            'eventi' => $events
        ]);
    }

    /**
     * The history page with reviewed events, old and upcoming.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @throws \PDOException
     */
    public function history(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $pageNum = 1;

        if (isset($args['page_num'])) {
            $pageNum = (int)$args['page_num'];
        }

        $events = $this->getEventsHistoryPaginated($pageNum);
        $eventsNumber = \count($this->getEventsHistory());

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/history.twig', [
            'utente' => $this->user,
            'eventi' => $events,
            'numero_eventi' => $eventsNumber,
            'pagina_attuale' => $pageNum
        ]);
    }

    /**
     * Perform a search for events that contains the string searched in title
     * or description.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @throws \RuntimeException
     */
    public function doSearchHistory(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        $validData = $this->checkSearchData($data);

        if ($validData === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        $events = $this->getEventsThatContains($data);
        $eventsNumber = \count($events);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/history.twig', [
            'utente' => $this->user,
            'eventi' => $events,
            'numero_eventi' => $eventsNumber,
            'pagina_attuale' => 1
        ]);
    }

    /**
     * Show the event page identified by $args['id'].
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
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

    /**
     * Get all the events that are available before the current timestamp and
     * order them by timestamp.
     *
     * @return array The events.
     * @throws \PDOException
     */
    private function getReviewedEvents(): array
    {
        return $this->eventModel->getReviewedEvents();
    }

    /**
     * Get all the events that are available and approved, even before the current time-date.
     * The events are only max PAGINATION_NUMBER per $pageNum.
     *
     * @param $pageNum int The page number.
     * @return array The events.
     * @throws \PDOException
     */
    private function getEventsHistoryPaginated($pageNum): array
    {
        return $this->eventModel->getEventsHistoryPaginated($pageNum);
    }

    /**
     * Get all the events that are available and approved, even before the current time-date.
     *
     * @return array The events.
     * @throws \PDOException
     */
    private function getEventsHistory(): array
    {
        return $this->eventModel->getEventsHistory();
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
     * Return the events, with merged associations, that contains $query in event title
     * or in the event description (or both). It supports the filters in $data.
     *
     * @param $data array The search data and filters to perform the query.
     * @return array The events with $query in title or description.
     * @throws \PDOException
     */
    private function getEventsThatContains($data): array
    {
        return $this->eventModel->getEventsThatContains($data);
    }

    /**
     * Check if $data contains valid data for the search action.
     *
     * @param $data array The data submitted by the user.
     * @return bool TRUE if the search data are valid, FALSE otherwise.
     */
    private function checkSearchData(&$data): bool
    {
        $data['stato'] = $this->validateState($data['stato']);

        return true;
    }

    /**
     * Check if $state is a string of them:
     * - disponibile
     * - concluso
     * - default
     * If so return the string. If not return the string "default".
     *
     * @param $state string The state of an event.
     * @return string The right string value of the state. If it's not a valid
     *     state it's returned "default".
     */
    private function validateState($state): string
    {
        switch (strtolower(trim($state))) {
            case 'disponibile':
                return 'disponibile';
            case 'concluso':
                return 'concluso';

            default:
                return 'default';
        }
    }
}
