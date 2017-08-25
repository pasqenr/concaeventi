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
        /*$user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $associationID = $args['id'];
        $association = $this->getSponsor($sponsorID);
        $members = $this->getAllMembers();
        $belongs = $this->getBelongsByAssociation($associationID);*/

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        /*return $this->render($response, 'associations/edit.twig', [
            'utente' => $user,
            'ass' => $association,
            'membri' => $members,
            'appartenenza' => $belongs
        ]);*/
    }

    public function doEdit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        /*$associationID = $args['id'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateAssociation($associationID, $parsedBody);

        if ($updated === false) {
            return $response->withRedirect($this->router->pathFor('error'));

        }

        return $response->withRedirect($this->router->pathFor('associations'));*/
    }

    public function delete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        /*if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        $associationID = $args['id'];
        $association = $this->getAssociation($associationID);*/

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        /*return $this->render($response, 'associations/delete.twig', [
            'utente' => $user,
            'ass' => $association
        ]);*/
    }

    public function doDelete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        /*$eventID = (int)$args['id'];
        $this->deleteAssociation($eventID);

        return $response->withRedirect($this->router->pathFor('associations'));*/
    }

    private function mergeSponsors($sponsors): array
    {
        if (empty($sponsors)) {
            return [];
        }

        $associationsWithMergedNames = [];
        $old = $sponsors[0];
        $assCount = count($sponsors);

        for ($i = 0, $j = 0; $i < $assCount; $i++, $j++) {
            if ($old['idAssociazione'] === $sponsors[$i]['idAssociazione'] &&
                $old['nomeUtente'] !== $sponsors[$i]['nomeUtente']) {
                $sponsors[$i]['nomeUtente'] .= ', ' . $old['nomeUtente'];

                if ($j !== 0) {
                    $j--;
                }
            }

            $associationsWithMergedNames[$j] = $sponsors[$i];

            $old = $sponsors[$i];
        }

        return $associationsWithMergedNames;
    }

    private function getSponsors(): array
    {
        $sth = $this->db->prepare('
            SELECT S.idSponsor, S.nome, S.logo
            FROM Sponsor S
        ');
        $sth->execute();

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

    private function createSponsor($data)
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
}