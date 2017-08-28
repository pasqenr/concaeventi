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

class FundingController extends Controller
{
    public function showAll(Request $request, Response $response)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $sponsors = $this->getFundings();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/fundings.twig', [
            'utente' => $user,
            'sponsors' => $sponsors
        ]);
    }

    public function create(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $events = $this->getEvents();
        $sponsors = $this->getSponsors();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/create.twig', [
            'utente' => $user,
            'events' => $events,
            'sponsors' => $sponsors
        ]);
    }

    public function doCreate(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $parsedBody = $request->getParsedBody();
        $created = $this->createFunding($parsedBody);

        if ($created === false) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        return $response->withRedirect($this->router->pathFor('fundings'));
    }

    public function edit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $fundingID = $args['id'];
        $funding = $this->getFunding($fundingID);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/edit.twig', [
            'utente' => $user,
            'funding' => $funding
        ]);
    }

    public function doEdit(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $associationID = $args['id'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateFunding($associationID, $parsedBody);

        if ($updated === false) {
            return $response->withRedirect($this->router->pathFor('error'));

        }

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

    public function delete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $fundingID = $args['id'];
        $funding = $this->getFunding($fundingID);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/delete.twig', [
            'utente' => $user,
            'funding' => $funding
        ]);
    }

    public function doDelete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = (int)$args['id'];
        $this->deleteFunding($eventID);

        return $response->withRedirect($this->router->pathFor('sponsors'));
    }

    private function getFundings(): array
    {
        $sth = $this->db->query('
            SELECT F.importo, F.idSponsor, F.idEvento, S.nome AS nomeSponsor, S.logo, E.titolo
            FROM Finanziamento F
            JOIN Sponsor S
            USING (idSponsor)
            JOIN Evento E
            USING (idEvento)
            ORDER BY S.idSponsor
        ');

        $fundings = $sth->fetchAll();
        $fundings['finanziamento'] = [];

        return $fundings;
    }

    private function getFunding($fundingID): array
    {
        $sth = $this->db->prepare('
            SELECT S.idSponsor, S.nome, S.logo
            FROM Sponsor S
            WHERE S.idSponsor = :idSponsor
        ');
        $sth->bindParam(':idSponsor', $fundingID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetch();
    }

    private function createFunding($data): bool
    {
        $sponsorID = $data['idSponsor'];
        $eventID = $data['idEvento'];
        $amount = $data['importo'];

        if ($amount !== "") {
            $amount = str_replace(',', '.', $amount);

            $sth = $this->db->prepare('
                INSERT INTO Finanziamento (
                  idSponsor, idEvento, importo, dataFinanziamento
                ) VALUES (
                  :idSponsor, :idEvento, :importo, CURRENT_TIMESTAMP
                )
            ');
            $sth->bindParam(':importo', $amount, \PDO::PARAM_STR);
        } else {
            $sth = $this->db->prepare('
                INSERT INTO Finanziamento (
                  idSponsor, idEvento, importo, dataFinanziamento
                ) VALUES (
                  :idSponsor, :idEvento, NULL, CURRENT_TIMESTAMP
                )
            ');
        }

        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function updateFunding($fundingID, $data): bool
    {
        $fundingName = $data['nome'];
        $logo = $data['logo'];

        $sth = $this->db->prepare('
            UPDATE Sponsor S
            SET S.nome = :nome, S.logo = :logo
            WHERE S.idSponsor = :idSponsor
        ');
        $sth->bindParam(':nome', $fundingName, \PDO::PARAM_STR);
        $sth->bindParam(':logo', $logo, \PDO::PARAM_STR);
        $sth->bindParam(':idSponsor', $fundingID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    private function deleteFunding($fundingID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Sponsor
            WHERE idSponsor = :idSponsor
        ');
        $sth->bindParam(':idSponsor', $fundingID, \PDO::PARAM_INT);

        return $sth->execute();
    }

    /**
     * Get all the events that are available before the current timestamp and order them by timestamp.
     *
     * @return array The events.
     */
    private function getEvents(): array
    {
        $sth = $this->db->query('
            SELECT E.idEvento, E.titolo
            FROM Evento E
            WHERE DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) > 0
        ');

        return $sth->fetchAll();
    }

    private function getSponsors(): array
    {
        $sth = $this->db->query('
            SELECT S.idSponsor, S.nome
            FROM Sponsor S
        ');

        return $sth->fetchAll();
    }
}