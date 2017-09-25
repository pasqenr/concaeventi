<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
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

    public function __construct($container)
    {
        parent::__construct($container);
        $this->eventModel = new EventModel($this->db);
        $this->fundingModel = new FundingModel($this->db);
        $this->sponsorModel = new SponsorModel($this->db);
    }

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
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('fundings'));
    }

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
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare il finanziamento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/edit.twig', [
            'utente' => $this->user,
            'funding' => $funding
        ]);
    }

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
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare il finanziamento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('fundings'));
    }

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
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare il finanziamento.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/delete.twig', [
            'utente' => $this->user,
            'funding' => $funding
        ]);
    }

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
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('fundings'));
    }

    private function getEventsWithFunding(): array
    {
        return $this->eventModel->getEventsWithFunding();
    }

    private function getFunding($eventID, $sponsorID): array
    {
        return $this->fundingModel->getFunding($eventID, $sponsorID);
    }

    private function createFunding($data): bool
    {
        $amount = $data['importo'];

        $amount_pattern = '[0-9]{1,6}.[0-9]{1,2}';

        if ($amount !== '') {
            $amount = str_replace(',', '.', $amount);

            if (!preg_match($amount_pattern, $amount)) {
                $this->setErrorMessage('Wrong amount format.',
                    'Formato valuta errato.');

                return false;
            }

            $data['importo'] = $amount;
        }

        return $this->fundingModel->createFunding($data);
    }

    private function updateFunding($eventID, $sponsorID, $data): bool
    {
        $amount = $data['importo'];

        $amount = str_replace(',', '.', $amount);
        $amount_pattern = '[0-9]{1,6}.[0-9]{1,2}';

        if (!preg_match($amount_pattern, $amount)) {
            $this->setErrorMessage('Wrong amount format.',
                'Formato valuta errato.');

            return false;
        }

        $data['importo'] = $amount;

        return $this->fundingModel->updateFunding($eventID, $sponsorID, $data);
    }

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
        /*$sth = $this->db->query('
            SELECT E.idEvento, E.titolo
            FROM Evento E
            WHERE DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) > 0
        ');

        return $sth->fetchAll();*/

        return $this->eventModel->getEvents();
    }

    /**
     * @return array
     */
    private function getSponsors(): array
    {
        /*$sth = $this->db->query('
            SELECT S.idSponsor, S.nome
            FROM Sponsor S
        ');

        return $sth->fetchAll();*/

        return $this->sponsorModel->getSponsors();
    }
}