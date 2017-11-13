<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
use \App\Helpers\ErrorHelper;
use \App\Models\SponsorModel;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;

/**
 * @property Router router
 * @property \PDO db
 */
class SponsorController extends Controller
{
    private $sponsorModel;
    private $errorHelper;

    /**
     * SponsorController constructor.
     * @param \Slim\Container $container
     */
    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->sponsorModel = new SponsorModel($this->db, $this->errorHelper);
    }

    /**
     * Show all the sponsor.
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

        $sponsors = $this->getSponsors();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/sponsors.twig', [
            'utente' => $this->user,
            'sponsor' => $sponsors
        ]);
    }

    /**
     * Create page for a new sponsor.
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed The rendered page.
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

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/create.twig', [
            'utente' => $this->user
        ]);
    }

    /**
     * Sponsor creation action.
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
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createSponsor($parsedBody);

        if ($created === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

    /**
     * Edit page for a sponsor.
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

        $sponsorID = $args['id'];
        try {
            $sponsor = $this->getSponsor($sponsorID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare lo sponsor.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/edit.twig', [
            'utente' => $this->user,
            'sponsor' => $sponsor
        ]);
    }

    /**
     * Edit sponsor action.
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
        $authorized = $this->session->auth(SessionHelper::DIRETTORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associationID = $args['id'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateSponsor($associationID, $parsedBody);

        if ($updated === false) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

    /**
     * Delete page for a sponsor.
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

        $sponsorID = $args['id'];
        try {
            $sponsor = $this->getSponsor($sponsorID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare lo sponsor.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/delete.twig', [
            'utente' => $this->user,
            'sponsor' => $sponsor
        ]);
    }

    /**
     * Delete sponsor action.
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
        try {
            $this->deleteSponsor($eventID);
        } catch (\PDOException $e) {

        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
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
     * Return the sponsor identified by $sponsorID.
     *
     * @param $sponsorID int A valid sponsor identifier.
     * @return array The sponsor identified by $sponsorID.
     */
    private function getSponsor($sponsorID): array
    {
        return $this->sponsorModel->getSponsor($sponsorID);
    }

    /**
     * Create a new sponsor using $data as values. The input array is
     * validated before submitting the query.
     *
     * @param $data array The values to insert in the new sponsor.
     * @return bool TRUE if the sponsor is created, FALSE otherwise.
     */
    private function createSponsor($data): bool
    {
        if ($this->checkSponsorData($data) === false) {
            return false;
        }

        return $this->sponsorModel->createSponsor($data);
    }

    /**
     * Update an already existent sponsor identified by $sponsorID with the
     * values in $data. The input array is validated before submitting
     * the query.
     *
     * @param $sponsorID int The sponsor identifier.
     * @param $data array The new values.
     * @return bool TRUE if the funding was updated, FALSE otherwise.
     */
    private function updateSponsor($sponsorID, $data): bool
    {
        if ($this->checkSponsorData($data) === false) {
            return false;
        }

        return $this->sponsorModel->updateSponsor($sponsorID, $data);
    }

    /**
     * Delete the event identified by $sponsorID.
     *
     * @param $sponsorID int The sponsor identifier.
     * @return bool TRUE if the funding was deleted, FALSE otherwise.
     */
    private function deleteSponsor($sponsorID): bool
    {
        return $this->sponsorModel->deleteSponsor($sponsorID);
    }

    /**
     * Validate the values in $data.
     *
     * @param $data array The values for the sponsor.
     * @return bool TRUE if the values are valid, FALSE otherwise.
     */
    private function checkSponsorData($data): bool
    {
        $sponsorName = $data['nome'];

        if ($sponsorName === '') {
            $this->errorHelper->setErrorMessage('Empty field.',
                'Un campo obbligatorio non è stato inserito.');

            return false;
        }

        return true;
    }
}