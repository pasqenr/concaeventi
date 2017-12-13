<?php

namespace App\Models;

use \App\Helpers\ErrorHelper;

/**
 * Class EventModel
 * @package App\Models
 * 
 * @property ErrorHelper errorHelper
 */
class EventModel extends Model
{
    private $PAGINATION_NUMBER = 10;

    /**
     * Get all the events that are available before the current timestamp and order them by timestamp.
     *
     * @param $userID int The current user identifier.
     * @return array The events.
     * @throws \PDOException
     */
    public function getEvents($userID): array
    {
        $sth = $this->db->prepare('
            SELECT U.idUtente, A.idAssociazione, E.idEvento, E.titolo, E.immagine, E.descrizione, E.istanteCreazione,
                   E.istanteInizio, E.istanteFine, E.pagina, E.revisionato, A2.nomeAssociazione AS nomeAssPrimaria,
                   A.nomeAssociazione, A.logo, U.nome AS nomeUtente, U.cognome AS cognomeUtente, U.email, U.ruolo,
                   A2.logo AS logoPrimario
            FROM Evento E
            LEFT JOIN Proporre P
            USING (idEvento)
            LEFT JOIN Associazione A
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
            LEFT JOIN Associazione A2
            ON (E.idAssPrimaria = A2.idAssociazione)
            WHERE E.idEvento = ANY (
              SELECT Pi.idEvento
              FROM Proporre Pi
              WHERE Pi.idAssociazione IN (
                SELECT APi.idAssociazione
                FROM Appartiene APi
                WHERE APi.idUtente = :idUtente
              )
            )
            ORDER BY E.istanteCreazione DESC
            LIMIT 50
        ');
        $sth->bindParam(':idUtente', $userID, \PDO::PARAM_INT);

        try {
            $sth->execute();
            $events = $sth->fetchAll();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('getEvents(): PDOException, check errorInfo.',
                'Recupero eventi: errore nell\'elaborazione dei dati.',
                $this->db->errorInfo());

            throw $e;
        }

        $events = $this->mergeAssociations($events);

        return $events;
    }

    /**
     * Get all the events that are available before the current timestamp and order them by timestamp that
     * are been reviewed.
     *
     * @return array The events.
     * @throws \PDOException
     */
    public function getReviewedEvents(): array
    {
        $sth = $this->db->query('
            SELECT U.idUtente, A.idAssociazione, E.idEvento, E.titolo, E.immagine, E.descrizione, E.istanteCreazione,
                   E.istanteInizio, E.istanteFine, E.pagina, E.revisionato, A2.nomeAssociazione AS nomeAssPrimaria,
                   A.nomeAssociazione, A.logo, U.nome AS nomeUtente, U.cognome AS cognomeUtente, U.email, U.ruolo,
                   A2.logo AS logoPrimario
            FROM Evento E
            LEFT JOIN Proporre P
            USING (idEvento)
            LEFT JOIN Associazione A
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
            LEFT JOIN Associazione A2
            ON (E.idAssPrimaria = A2.idAssociazione)
            WHERE DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) >= 0
              AND E.revisionato = TRUE
            ORDER BY E.istanteInizio
        ');
        try {
            $events = $sth->fetchAll();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('getEvents(): PDOException, check errorInfo.',
                'Recupero eventi: errore nell\'elaborazione dei dati.',
                $this->db->errorInfo());

            throw $e;
        }

        $events = $this->mergeAssociations($events);

        return $events;
    }

    /**
     * Get all the events that are available and approved, even before the current time-date.
     *
     * @return array The events.
     * @throws \PDOException
     */
    public function getEventsHistory(): array
    {
        $sth = $this->db->query('
            SELECT U.idUtente, A.idAssociazione, E.idEvento, E.titolo, E.immagine, E.descrizione, E.istanteCreazione,
                   E.istanteInizio, E.istanteFine, E.pagina, E.revisionato, A2.nomeAssociazione AS nomeAssPrimaria,
                   A.nomeAssociazione, A.logo, U.nome AS nomeUtente, U.cognome AS cognomeUtente, U.email, U.ruolo,
                   A2.logo AS logoPrimario
            FROM Evento E
            LEFT JOIN Proporre P
            USING (idEvento)
            LEFT JOIN Associazione A
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
            LEFT JOIN Associazione A2
            ON (E.idAssPrimaria = A2.idAssociazione)
            WHERE E.revisionato = TRUE
            ORDER BY E.istanteInizio DESC
        ');
        try {
            $events = $sth->fetchAll();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('getEvents(): PDOException, check errorInfo.',
                'Recupero eventi: errore nell\'elaborazione dei dati.',
                $this->db->errorInfo());

            throw $e;
        }

        $events = $this->mergeAssociations($events);


        return $events;
    }

    /**
     * Get all the events that are available and approved, even before the current time-date.
     * The events are only max PAGINATION_NUMBER per $pageNum.
     *
     * @param $pageNum int The page number.
     * @return array The events.
     * @throws \PDOException
     */
    public function getEventsHistoryPaginated($pageNum): array
    {
        $events = $this->getEventsHistory();
        $events = $this->paginateEvents($events, $pageNum);

        return $events;
    }

    /**
     * Get the event identified by the ID.
     *
     * @param $eventID
     * @return array The events.
     * @throws \PDOException
     * @internal param int $id The event identifier.
     */
    public function getEvent($eventID): array
    {
        $id = (int)$eventID;

        $sth = $this->db->prepare('
            SELECT U.idUtente, A.idAssociazione, E.idEvento, E.titolo, E.immagine, E.descrizione, E.istanteCreazione,
                   E.istanteInizio, E.istanteFine, E.pagina, E.revisionato, A2.nomeAssociazione AS nomeAssPrimaria, 
                   A2.idAssociazione AS idAssPrimaria, A2.stile, A2.telefono, A.nomeAssociazione, A.logo, 
                   U.nome AS nomeUtente, U.cognome AS cognomeUtente, U.email, U.ruolo, A2.logo AS logoPrimario
            FROM Evento E
            LEFT JOIN Proporre P
            USING (idEvento)
            LEFT JOIN Associazione A
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
            LEFT JOIN Associazione A2
            ON (E.idAssPrimaria = A2.idAssociazione)
            WHERE E.idEvento = :eventID
        ');
        $sth->bindParam(':eventID', $id, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        $events = $sth->fetchAll();

        if (empty($events)) {
            return [];
        }

        $events = $this->mergeAssociations($events);

        return $events[0];
    }

    /**
     * Return the identifier of the last event inserted, that is, the larger
     * identifier.
     *
     * @return int The identifier of the last event inserted.
     * @throws \PDOException
     */
    public function getLastEventID(): int
    {
        $sth = $this->db->prepare('
            SELECT E.idEvento
            FROM Evento E
            ORDER BY E.idEvento DESC
            LIMIT 1
        ');

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return (int)$sth->fetch()['idEvento'];
    }

    /**
     * Create a new event created by the user with $userID and with the $data array.
     *
     * @param int $userID The unique user identifier that created the event.
     * @param array $data The event fields.
     * @return bool TRUE if the events was created, FALSE if not. Errors are set internally.
     */
    public function createEvent($userID, $data): bool
    {
        $idUtente = $userID;
        $titolo = $data['titolo'];
        $descrizione = $data['descrizione'];
        $istanteInizio = $data['istanteInizio'];
        $istanteFine = $data['istanteFine'];
        $revisionato = $data['revisionato'];
        $associazioni = $data['associazioni'];
        $idAssPrimaria = $data['assPrimaria'];

        if ($revisionato === 'on') {
            $revisionato = 1;
        } else {
            $revisionato = 0;
        }

        $this->db->beginTransaction();

        $sth = $this->db->prepare('
            INSERT INTO Evento (
                idEvento, titolo, descrizione, istanteCreazione, istanteInizio, istanteFine, 
                revisionato, idUtente, idAssPrimaria
            )
            VALUES (
                NULL, :titolo, :descrizione, CURRENT_TIMESTAMP, :istanteInizio, :istanteFine, 
                :revisionato, :idUtente, :idAssPrimaria
            )
        ');
        $sth->bindParam(':titolo', $titolo, \PDO::PARAM_STR);
        $sth->bindParam(':descrizione', $descrizione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteInizio', $istanteInizio, \PDO::PARAM_STR);
        $sth->bindParam(':istanteFine', $istanteFine, \PDO::PARAM_STR);
        $sth->bindParam(':revisionato', $revisionato, \PDO::PARAM_BOOL);
        $sth->bindParam(':idUtente', $idUtente, \PDO::PARAM_INT);
        $sth->bindParam(':idAssPrimaria', $idAssPrimaria, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile creare l\'evento.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        try {
            $eventID = $this->getLastEventID();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile recupeare l\'ultimo evento.');

            $this->db->rollBack();

            return false;
        }

        /** @var $associazioni int[] */
        foreach ($associazioni as $ass) {
            try {
                $this->addPropose($eventID, $ass);
            } catch (\PDOException $e) {
                $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                    'Impossibile associare le associazioni.');

                $this->db->rollBack();

                return false;
            }
        }
        $this->db->commit();

        return true;
    }

    /**
     * Edit an event identified by $update['id'] and the data in $update.
     *
     * @param array $update The array with the changed fields.
     * @return bool TRUE if the event was updated, FALSE otherwise.
     */
    public function updateEvent($update): bool
    {
        $eventID = $update['id'];
        $titolo = $update['titolo'];
        $descrizione = $update['descrizione'];
        $istanteCreazione = $update['istanteCreazione'];
        $istanteInizio = $update['istanteInizio'];
        $istanteFine = $update['istanteFine'];
        $revisionato = $update['revisionato'];
        $idAssPrimaria = $update['assPrimaria'];

        if ($revisionato === 'on') {
            $revisionato = 1;
        } else {
            $revisionato = 0;
        }

        $this->db->beginTransaction();

        $sth = $this->db->prepare('
            UPDATE Evento E 
            SET E.titolo = :titolo, E.descrizione = :descrizione, 
                E.istanteCreazione = :istanteCreazione, E.istanteInizio = :istanteInizio, E.istanteFine = :istanteFine,
                E.revisionato = :revisionato, E.idAssPrimaria = :idAssPrimaria
            WHERE E.idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':titolo', $titolo, \PDO::PARAM_STR);
        $sth->bindParam(':descrizione', $descrizione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteCreazione', $istanteCreazione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteInizio', $istanteInizio, \PDO::PARAM_STR);
        $sth->bindParam(':istanteFine', $istanteFine, \PDO::PARAM_STR);
        $sth->bindParam(':revisionato', $revisionato, \PDO::PARAM_INT);
        $sth->bindParam(':idAssPrimaria', $idAssPrimaria, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare l\'evento.',
                $this->db->errorInfo());

            $this->db->rollBack();

            return false;
        }

        try {
            $this->deleteFromProposes($eventID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare le precedenti associazioni collegate all\'evento.');

            $this->db->rollBack();

            return false;
        }

        try {
            $this->createProposes($eventID, $update);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare le precedenti associazioni collegate all\'evento.');

            $this->db->rollBack();

            return false;
        }

        $this->db->commit();

        return true;
    }

    /**
     * Delete the event identified by $eventID.
     *
     * @param int $eventID The unique event identifier.
     * @return bool
     */
    public function deleteEvent($eventID): bool
    {
        $this->db->beginTransaction();

        try {
            $this->deleteFromProposes($eventID);
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare le associazioni collegate all\'evento.');

            $this->db->rollBack();

            return false;
        }

        $sth = $this->db->prepare('
            DELETE
            FROM Evento
            WHERE idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare l\'evento.',
                $this->db->errorInfo());
            
            $this->db->rollBack();

            return false;
        }

        $this->db->commit();

        return true;
    }

    /**
     * Update an event page data, identified by $eventID an using the data
     * in $data.
     *
     * @param int $eventID A valid event identifier.
     * @param array $data New values for the event page.
     * @return bool TRUE if the page have been changed, FALSE otherwise.
     */
    public function updatePage($eventID, $data): bool
    {
        $imageField = '';

        if ($data['immagine'] !== '') {
            $imageField = ', E.immagine = :immagine';
        }

        $sth = $this->db->prepare("
            UPDATE Evento E
            SET E.pagina = :pagina
              $imageField
            WHERE E.idEvento = :eventID
        ");
        $sth->bindParam(':pagina', $data['pagina'], \PDO::PARAM_STR);
        $sth->bindParam(':eventID', $eventID, \PDO::PARAM_INT);
        if ($data['immagine'] !== '') {
            $sth->bindParam(':immagine', $data['immagine'], \PDO::PARAM_STR);
        }

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare la pagina.',
                $this->db->errorInfo());

            return false;
        }

        return true;
    }

    /**
     * Return the all the events with the relative funding in the column
     * 'finanziamento'.
     *
     * @return array The events with them funding.
     */
    public function getEventsWithFunding(): array
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

    /**
     * Return the events, with merged associations, that contains $query in event title
     * or in the event description (or both). It supports the filters in $data.
     *
     * @param $data array The search data and filters to perform the query.
     * @return array The events with $query in title or description.
     * @throws \PDOException
     */
    public function getEventsThatContains($data): array
    {
        $query = $data['search_query'];
        $state = $data['stato'];
        $query = "%$query%";

        $optionalDataState = '1';
        if ($state === 'disponibile') {
            $optionalDataState = 'DATEDIFF(E.istanteInizio, E.istanteFine) > 0';
        } else if ($state === 'concluso') {
            $optionalDataState = 'DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) <= 0';
        }

        $sth = $this->db->prepare('
            SELECT U.idUtente, A.idAssociazione, E.idEvento, E.titolo, E.immagine, E.descrizione, E.istanteCreazione,
                   E.istanteInizio, E.istanteFine, E.pagina, E.revisionato, A2.nomeAssociazione AS nomeAssPrimaria,
                   A.nomeAssociazione, A.logo, U.nome AS nomeUtente, U.cognome AS cognomeUtente, U.email, U.ruolo,
                   A2.logo AS logoPrimario
            FROM Evento E
            LEFT JOIN Proporre P
            USING (idEvento)
            LEFT JOIN Associazione A
            USING (idAssociazione)
            LEFT JOIN Utente U
            USING (idUtente)
            LEFT JOIN Associazione A2
            ON (E.idAssPrimaria = A2.idAssociazione)
            WHERE E.revisionato = TRUE
            AND (E.titolo LIKE ?
              OR E.descrizione LIKE ?
            )
            AND '.$optionalDataState.'
            ORDER BY E.istanteInizio DESC
        ');

        $sth->bindParam(1, $query, \PDO::PARAM_STR);
        $sth->bindParam(2, $query, \PDO::PARAM_STR);
        try {
            $sth->execute();
            $events = $sth->fetchAll();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('getEventsThatContains(): PDOException, 
                check errorInfo.',
                'Recupero eventi: errore nell\'elaborazione dei dati.',
                $this->db->errorInfo());

            throw $e;
        }

        $events = $this->mergeAssociations($events);

        return $events;
    }

    /**
     * Merge the Associtations on the rows with the same idEvento. The separator used is comma and space (', ').
     * If there aren't events the function returns an empty array.
     *
     * @param $events array The events fetched from the database.
     * @return array  The events merged with the Associtations in the same Event. If the array is empty the function
     *                return an empty array.
     */
    private function mergeAssociations($events): array
    {
        if (empty($events)) {
            return [];
        }

        $eventsWithAssociations = [];
        $old = $events[0];
        $eventsCount = \count($events);

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0, $j = 0; $i < $eventsCount; $i++, $j++) {
            if ($old['idEvento'] === $events[$i]['idEvento'] &&
                $old['nomeAssociazione'] !== $events[$i]['nomeAssociazione']) {
                $events[$i]['nomeAssociazione'] .= ', ' . $old['nomeAssociazione'];
                $events[$i]['logo'] .= ', ' . $old['logo'];

                if ($j !== 0) {
                    $j--;
                }
            }

            $eventsWithAssociations[$j] = $events[$i];

            $old = $events[$i];
        }

        return $eventsWithAssociations;
    }

    /**
     * Add a propose for the event identified by $eventID by the association
     * identified by $associationID.
     *
     * @param int $eventID A valid event identifier.
     * @param int $associationID A valid association identifier.
     * @return bool TRUE if the propose have been added, FALSE otherwise.
     * @throws \PDOException
     */
    private function addPropose($eventID, $associationID): bool
    {
        $sth = $this->db->prepare('
            INSERT INTO Proporre (
                idEvento, idAssociazione
            )
            VALUES (
                :idEvento, :idAssociazione
            )
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':idAssociazione', $associationID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return true;
    }

    /**
     * Delete all the proposes to the event identified by $eventID.
     *
     * @param int $eventID A valid event identifier.
     * @return bool TRUE if the proposes have been deleted, FALSE otherwise.
     * @throws \PDOException
     */
    private function deleteFromProposes($eventID): bool
    {

        $sth = $this->db->prepare('
            DELETE
            FROM Proporre
            WHERE idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            throw $e;
        }

        return true;
    }

    /**
     * Add a propose to the event identified by $eventID for every
     * association identifier in $event.
     *
     * @param int $eventID A valid event identifier.
     * @param array $event The event with the column 'associazioni' that
     *        contains a list of associations ids.
     * @return bool TRUE if the proposes have been created, FALSE otherwise.
     * @throws \PDOException
     */
    private function createProposes($eventID, $event): bool
    {
        $associationsIds = $event['associazioni'];

        /** @var $associationsIds int[] */
        foreach ($associationsIds as $associationsID) {
            $this->addPropose($eventID, $associationsID);
        }

        return true;
    }

    /**
     * Return an array of events with each of them that contains a sub-array
     * in the column 'finanziamento' with the funding data.
     *
     * @param array $fundings Repeated events with a different funding for
     *        each row.
     * @return array A list of events with funding information as sub-list.
     */
    private function moveFundingInEvents($fundings): array
    {
        if ($fundings === []) {
            return [];
        }

        $eventsWithFundings = [];
        $fundingsCount = \count($fundings);

        for ($i = $j = $k = 0; $i < $fundingsCount; $i += $j, $j = $i, $k = 0) {
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

    private function paginateEvents(&$events, $pageNum)
    {
        $offset = ($pageNum - 1) * $this->PAGINATION_NUMBER;
        $length = $this->PAGINATION_NUMBER;

        return \array_slice($events, $offset, $length, true);
    }
}