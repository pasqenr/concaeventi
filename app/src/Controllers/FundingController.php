<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
use \App\Helpers\ErrorHelper;
use \App\Models\EventModel;
use \App\Models\FundingModel;
use \App\Models\SponsorModel;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;

/**
 * @property Router router
 * @property \PDO db
 */

class FundingController extends Controller
{
    private $eventModel;
    private $fundingModel;
    private $sponsorModel;
    private $errorHelper;

    /**
     * FundingController constructor.
     * @param \Slim\Container $container
     */
    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->eventModel = new EventModel($this->db, $this->errorHelper);
        $this->fundingModel = new FundingModel($this->db, $this->errorHelper);
        $this->sponsorModel = new SponsorModel($this->db, $this->errorHelper);
    }

    /**
     * Show all the funding.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function showAll(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventsWithFunding = $this->getEventsWithFunding();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/fundings.twig', [
            'utente' => $this->user,
            'eventi' => $eventsWithFunding
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
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $events = $this->getEvents();
        $sponsors = $this->getSponsors();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/create.twig', [
            'utente' => $this->user,
            'events' => $events,
            'sponsors' => $sponsors
        ]);
    }

    /**
     * Event creation action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function doCreate(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createFunding($parsedBody);

        if ($created === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('fundings'));
    }

    /**
     * Edit page for an event.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function edit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = $args['eventID'];
        $sponsorID = $args['sponsorID'];

        try {
            $funding = $this->getFunding($eventID, $sponsorID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare il finanziamento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/edit.twig', [
            'utente' => $this->user,
            'funding' => $funding
        ]);
    }

    /**
     * Edit event action.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function doEdit(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = $args['eventID'];
        $sponsorID = $args['sponsorID'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateFunding($eventID, $sponsorID, $parsedBody);

        if ($updated === false) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare il finanziamento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('fundings'));
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

        $eventID = $args['eventID'];
        $sponsorID = $args['sponsorID'];

        try {
            $funding = $this->getFunding($eventID, $sponsorID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare il finanziamento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/delete.twig', [
            'utente' => $this->user,
            'funding' => $funding
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

        $eventID = $args['eventID'];
        $sponsorID = $args['sponsorID'];
        try {
            $this->deleteFunding($eventID, $sponsorID);
        } catch (\PDOException $e) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('fundings'));
    }

    /**
     * Return the all the events with the relative funding in the column
     * 'finanziamento'.
     *
     * @return array The events with them funding.
     */
    private function getEventsWithFunding(): array
    {
        return $this->eventModel->getEventsWithFunding();
    }

    /**
     * Return the all the funding of the event identified by $eventID
     * sponsored by the sponsor identified by $sponsorID.
     *
     * @param $eventID int A valid event identifier.
     * @param $sponsorID int A valid sponsor identifier.
     * @return array The funding to the event $eventID by the sponsor
     *         $sponsorID.
     * @throws \PDOException
     */
    private function getFunding($eventID, $sponsorID): array
    {
        return $this->fundingModel->getFunding($eventID, $sponsorID);
    }

    /**
     * Create a new funding using $data as values. The input array is
     * validated before submitting the query.
     *
     * @param $data array The values to insert in the new funding.
     * @return bool TRUE if the funding is created, FALSE otherwise.
     */
    private function createFunding($data): bool
    {
        if ($this->checkFundingCheck($data) === false) {
            return false;
        }

        return $this->fundingModel->createFunding($data);
    }

    /**
     * Update an already existent funding identified by $eventID and $sponsorID
     * with the values in $data. The input array is validated before submitting
     * the query.
     *
     * @param $eventID int The event identifier.
     * @param $sponsorID int The sponsor identifier.
     * @param $data array The new values.
     * @return bool TRUE if the funding was updated, FALSE otherwise.
     */
    private function updateFunding($eventID, $sponsorID, $data): bool
    {
        if ($this->checkFundingCheck($data) === false) {
            return false;
        }

        return $this->fundingModel->updateFunding($eventID, $sponsorID, $data);
    }

    /**
     * Delete the event identified by $eventID and $sponsorID.
     *
     * @param $eventID int The event identifier.
     * @param $sponsorID int The sponsor identifier.
     * @return bool TRUE if the funding was deleted, FALSE otherwise.
     */
    private function deleteFunding($eventID, $sponsorID): bool
    {
        return $this->fundingModel->deleteFunding($eventID, $sponsorID);
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
     * Return all the sponsor.
     *
     * @return array All the sponsor.
     */
    private function getSponsors(): array
    {
        return $this->sponsorModel->getSponsors();
    }

    /**
     * Validate the values in $data.
     *
     * @param $data array The values for the funding.
     * @return bool TRUE if the values are valid, FALSE otherwise.
     */
    private function checkFundingCheck(&$data): bool
    {
        $amount = $data['importo'];

        $amount_pattern = '/^\d{1,6}[.]?\d{1,2}$/';

        if ($amount !== '') {
            $amount = str_replace(',', '.', $amount);

            if (preg_match($amount_pattern, $amount) !== 1) {
                $this->errorHelper->setErrorMessage('Wrong amount format.',
                    'Formato valuta errato.');

                return false;
            }

            $data['importo'] = $amount;
        } else {
            $data['importo'] = '';
        }

        return true;
    }
}