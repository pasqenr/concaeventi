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
class SponsorController extends Controller
{
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
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

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
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare lo sponsor.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/edit.twig', [
            'utente' => $this->user,
            'sponsor' => $sponsor
        ]);
    }

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
                'err' => $this->getErrorMessage()
            ]);
        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

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
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare lo sponsor.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/delete.twig', [
            'utente' => $this->user,
            'sponsor' => $sponsor
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
        try {
            $this->deleteSponsor($eventID);
        } catch (\PDOException $e) {

        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

    private function getSponsors(): array
    {
        $sth = $this->db->query('
            SELECT S.idSponsor, S.nome, S.logo
            FROM Sponsor S
        ');

        return $sth->fetchAll();
    }

    private function getSponsor($sponsorID): array
    {
        $sth = $this->db->prepare('
            SELECT S.idSponsor, S.nome, S.logo
            FROM Sponsor S
            WHERE S.idSponsor = :idSponsor
        ');
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetch();
    }

    private function createSponsor($data): bool
    {
        $sponsorName = $data['nome'];
        $logo = $data['logo'];

        if ($sponsorName === '') {
            $this->setErrorMessage('Empty field.',
                'Un campo obbligatorio non è stato inserito.');

            return false;
        }

        $sth = $this->db->prepare('
            INSERT INTO Sponsor (
              idSponsor, nome, logo
            ) VALUES (
              NULL, :nome, :logo
            )
        ');
        $sth->bindParam(':nome', $sponsorName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile creare lo sponsor.');

            return false;
        }

        return true;
    }

    private function updateSponsor($sponsorID, $data): bool
    {
        $sponsorName = $data['nome'];
        $logo = $data['logo'];

        if ($sponsorName === '') {
            $this->setErrorMessage('Empty field.',
                'Un campo obbligatorio non è stato compilato.');

            return false;
        }

        $sth = $this->db->prepare('
            UPDATE Sponsor S
            SET S.nome = :nome, S.logo = :logo
            WHERE S.idSponsor = :idSponsor
        ');
        $sth->bindParam(':nome', $sponsorName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare lo sponsor.');

            return false;
        }

        return true;
    }

    private function deleteSponsor($sponsorID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Sponsor
            WHERE idSponsor = :idSponsor
        ');
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare lo sponsor.');

            return false;
        }

        return true;
    }
}