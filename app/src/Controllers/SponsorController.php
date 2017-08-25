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
    public function showAll(Request $request, Response $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $sponsors = $this->getSponsors();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/sponsors.twig', [
            'utente' => $user,
            'sponsor' => $sponsors
        ]);
    }

    public function create(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/create.twig', [
            'utente' => $user
        ]);
    }

    public function doCreate(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createSponsor($parsedBody);

        if ($created === false) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

    public function edit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $sponsorID = $args['id'];
        $sponsor = $this->getSponsor($sponsorID);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/edit.twig', [
            'utente' => $user,
            'sponsor' => $sponsor
        ]);
    }

    public function doEdit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $associationID = $args['id'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateSponsor($associationID, $parsedBody);

        if ($updated === false) {
            return $response->withRedirect($this->router->pathFor('error'));

        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

    public function delete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $sponsorID = $args['id'];
        $sponsor = $this->getSponsor($sponsorID);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'sponsors/delete.twig', [
            'utente' => $user,
            'sponsor' => $sponsor
        ]);
    }

    public function doDelete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $eventID = (int)$args['id'];
        $this->deleteAssociation($eventID);

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
        $nomeSponsor = $data['nome'];
        $logo = $data['logo'];

        $sth = $this->db->prepare('
            INSERT INTO Sponsor (
              idSponsor, nome, logo
            ) VALUES (
              NULL, :nome, :logo
            )
        ');
        $sth->bindParam(':nome', $nomeSponsor, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);

        return $sth->execute();
    }

    private function updateSponsor($sponsorID, $data): bool
    {
        $sponsorName = $data['nome'];
        $logo = $data['logo'];

        $sth = $this->db->prepare('
            UPDATE Sponsor S
            SET S.nome = :nome, S.logo = :logo
            WHERE S.idSponsor = :idSponsor
        ');
        $sth->bindParam(':nome', $sponsorName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function deleteAssociation($sponsorID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Sponsor
            WHERE idSponsor = :idSponsor
        ');
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);

        return $sth->execute();
    }
}