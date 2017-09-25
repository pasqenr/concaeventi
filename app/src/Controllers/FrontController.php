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

    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->eventModel = new EventModel($this->db, $this->errorHelper);
    }

    public function home(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $events = $this->getEvents();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/home.twig', [
            'utente' => $this->user,
            'eventi' => $events
        ]);
    }

    public function history(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $events = $this->getEventsHistory();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/history.twig', [
            'utente' => $this->user,
            'eventi' => $events
        ]);
    }

    /**
     * Get all the events that are available before the current timestamp and order them by timestamp.
     *
     * @return array The events.
     * @throws \PDOException
     */
    private function getEvents(): array
    {
        return $this->eventModel->getEvents();
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
}
