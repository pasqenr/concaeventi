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

        $eventsWithFunding = $this->getEventsWithFunding();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'funding/fundings.twig', [
            'utente' => $user,
            'eventi' => $eventsWithFunding
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

        $eventID = $args['eventID'];
        $sponsorID = $args['sponsorID'];
        $funding = $this->getFunding($eventID, $sponsorID);

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

        $eventID = $args['eventID'];
        $sponsorID = $args['sponsorID'];
        $parsedBody = $request->getParsedBody();
        $updated = $this->updateFunding($eventID, $sponsorID, $parsedBody);

        if ($updated === false) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        return $response->withRedirect($this->router->pathFor('fundings'));
    }

    public function delete(Request $request, Response $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::DIRETTORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        $eventID = $args['eventID'];
        $sponsorID = $args['sponsorID'];
        $funding = $this->getFunding($eventID, $sponsorID);

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

    private function getEventsWithFunding(): array
    {
        $sth = $this->db->query('
            SELECT F.idSponsor, F.idEvento, F.importo, F.dataFinanziamento, S.nome AS nomeSponsor, S.logo, E.titolo
            FROM Finanziamento F
            JOIN Sponsor S
            USING (idSponsor)
            JOIN Evento E
            USING (idEvento)
            ORDER BY S.idSponsor
        ');

        $fundings = $sth->fetchAll();

        if ($fundings === []) {
            return [];
        }

        $fundings = $this->moveFundingInEvents($fundings);

        return $fundings;
    }

    private function getFunding($eventID, $sponsorID): array
    {
        $sth = $this->db->prepare('
            SELECT F.idEvento, F.idSponsor, F.importo, F.dataFinanziamento, E.titolo, S.nome
            FROM Finanziamento F
            JOIN Evento E
            ON (F.idEvento)
            JOIN Sponsor S
            ON (F.idSponsor)
            WHERE F.idEvento = :idEvento
              AND F.idSponsor = :idSponsor
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetch();
    }

    private function createFunding($data): bool
    {
        $sponsorID = $data['idSponsor'];
        $eventID = $data['idEvento'];
        $amount = $data['importo'];

        if ($amount !== '') {
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

    private function updateFunding($eventID, $sponsorID, $data): bool
    {
        $amount = $data['importo'];

        $amount = str_replace(',', '.', $amount);

        $sth = $this->db->prepare('
            UPDATE Finanziamento F
            SET F.importo = :importo
            WHERE F.idEvento = :idEvento
              AND F.idSponsor = :idSponsor
        ');
        $sth->bindParam(':importo', $amount, \PDO::PARAM_STR);
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);

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

    private function moveFundingInEvents($fundings): array
    {
        if ($fundings === []) {
            return [];
        }

        $fundingsCount = count($fundings);
        $old = $fundings[0];
        $eventsWithFundings[0]['idEvento'] = $fundings[0]['idEvento'];
        $eventsWithFundings[0]['titolo'] = $fundings[0]['titolo'];
        $eventsWithFundings[0]['finanziamento'][0]['idSponsor'] = $fundings[0]['idSponsor'];
        $eventsWithFundings[0]['finanziamento'][0]['nomeSponsor'] = $fundings[0]['nomeSponsor'];
        $eventsWithFundings[0]['finanziamento'][0]['logo'] = $fundings[0]['logo'];
        $eventsWithFundings[0]['finanziamento'][0]['importo'] = $fundings[0]['importo'];
        $eventsWithFundings[0]['finanziamento'][0]['dataFinanziamento'] = $fundings[0]['dataFinanziamento'];

        if ($fundingsCount === 1) {
            return $eventsWithFundings;
        }

        for ($i = 1, $j = 0; $i !== $fundingsCount; $i++) {
            if ($fundings[$i]['idEvento'] !== $old['idEvento']) {
                $eventsWithFundings[$i]['idEvento'] = $fundings[$i]['idEvento'];
                $eventsWithFundings[$i]['titolo']   = $fundings[$i]['titolo'];
            }

            if ($fundings[$i]['idEvento']  === $old['idEvento'] &&
                $fundings[$i]['idSponsor'] !== $old['idSponsor']) {
                $j++;
                $eventsWithFundings[$i - $j]['finanziamento'][$j]['idSponsor']         = $fundings[$i]['idSponsor'];
                $eventsWithFundings[$i - $j]['finanziamento'][$j]['nomeSponsor']       = $fundings[$i]['nomeSponsor'];
                $eventsWithFundings[$i - $j]['finanziamento'][$j]['logo']              = $fundings[$i]['logo'];
                $eventsWithFundings[$i - $j]['finanziamento'][$j]['importo']           = $fundings[$i]['importo'];
                $eventsWithFundings[$i - $j]['finanziamento'][$j]['dataFinanziamento'] = $fundings[$i]['dataFinanziamento'];
            }

            if ($j === 0 && $fundings[$i]['idEvento'] !== $old['idEvento']) {
                $eventsWithFundings[$i]['finanziamento'][$j]['idSponsor']         = $fundings[$i]['idSponsor'];
                $eventsWithFundings[$i]['finanziamento'][$j]['nomeSponsor']       = $fundings[$i]['nomeSponsor'];
                $eventsWithFundings[$i]['finanziamento'][$j]['logo']              = $fundings[$i]['logo'];
                $eventsWithFundings[$i]['finanziamento'][$j]['importo']           = $fundings[$i]['importo'];
                $eventsWithFundings[$i]['finanziamento'][$j]['dataFinanziamento'] = $fundings[$i]['dataFinanziamento'];
            }


            $old = $fundings[$i];
        }

        return $eventsWithFundings;
    }
}