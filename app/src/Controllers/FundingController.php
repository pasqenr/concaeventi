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
        $sth = $this->db->query('
            SELECT F.idSponsor, F.idEvento, F.importo, F.dataFinanziamento, S.nome AS nomeSponsor, S.logo, E.titolo
            FROM Finanziamento F
            JOIN Sponsor S
            USING (idSponsor)
            JOIN Evento E
            USING (idEvento)
            ORDER BY F.idEvento DESC, F.idSponsor
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

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return $sth->fetch();
    }

    private function createFunding($data): bool
    {
        $sponsorID = $data['idSponsor'];
        $eventID = $data['idEvento'];
        $amount = $data['importo'];

        $amount_pattern = '[0-9]{1,6}.[0-9]{1,2}';

        if ($amount !== '') {
            $amount = str_replace(',', '.', $amount);

            if (!preg_match($amount_pattern, $amount)) {
                $this->setErrorMessage('Wrong amount format.',
                    'Formato valuta errato.');

                return false;
            }

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

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Creazione finanziamento: errore nell\'elaborazione dei dati.');

            return false;
        }

        return true;
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

        $sth = $this->db->prepare('
            UPDATE Finanziamento F
            SET F.importo = :importo
            WHERE F.idEvento = :idEvento
              AND F.idSponsor = :idSponsor
        ');
        $sth->bindParam(':importo', $amount, \PDO::PARAM_STR);
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare il finanziamento.');

            return false;
        }

        return true;
    }

    private function deleteFunding($eventID, $sponsorID): bool
    {
        $sth = $this->db->prepare('
            DELETE
            FROM Finanziamento
            WHERE idEvento = :idEvento
              AND idSponsor = :idSponsor
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':idSponsor', $sponsorID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare il finanziamento.');

            return false;
        }

        return true;
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

        $eventsWithFundings = [];
        $fundingsCount = count($fundings);

        for ($i = $j = $k = 0; $i <= $fundingsCount; $i += $j, $j = $i, $k = 0) {
            $eventsWithFundings[$i]['idEvento'] = $fundings[$i]['idEvento'];
            $eventsWithFundings[$i]['titolo'] = $fundings[$i]['titolo'];

            do {
                $eventsWithFundings[$i]['finanziamento'][$k]['idSponsor'] = $fundings[$j]['idSponsor'];
                $eventsWithFundings[$i]['finanziamento'][$k]['nomeSponsor'] = $fundings[$j]['nomeSponsor'];
                $eventsWithFundings[$i]['finanziamento'][$k]['logo'] = $fundings[$j]['logo'];
                $eventsWithFundings[$i]['finanziamento'][$k]['importo'] = $fundings[$j]['importo'];
                $eventsWithFundings[$i]['finanziamento'][$k]['dataFinanziamento'] = $fundings[$j]['dataFinanziamento'];

                $j++;
                $k++;

                if ($j >= $fundingsCount) {
                    break;
                }
            } while ($fundings[$j]['idEvento'] === $fundings[$j-1]['idEvento']);
        }

        return $eventsWithFundings;
    }
}