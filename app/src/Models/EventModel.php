<?php

namespace App\Models;

/**
 * Class EventModel
 * @package App\Models
 *
 * @property \PDO db
 */
class EventModel extends Model
{
    /**
     * Get all the events that are available before the current timestamp and order them by timestamp.
     *
     * @return array The events.
     * @throws \PDOException
     */
    public function getEvents(): array
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
            WHERE DATEDIFF(E.istanteFine, CURRENT_TIMESTAMP) > 0
            ORDER BY E.istanteInizio
        ');
        try {
            $events = $sth->fetchAll();
        } catch (\PDOException $e) {
            /*$this->setErrorMessage('getEvents(): PDOException, check errorInfo.',
                'Recupero eventi: errore nell\'elaborazione dei dati.',
                $this->db->errorInfo());*/

            throw new GenericPDOException($e);
        }

        $events = $this->mergeAssociations($events);

        return $events;
    }

    /**
     * @return array
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
            /*$this->setErrorMessage('getEvents(): PDOException, check errorInfo.',
                'Recupero eventi: errore nell\'elaborazione dei dati.',
                $this->db->errorInfo());*/

            throw $e;
        }

        $events = $this->mergeAssociations($events);

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
     * @return int
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
     * @param $userID
     * @param $data
     * @return bool
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
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile creare l\'evento.',
                $this->db->errorInfo());*/

            $this->db->rollBack();

            return false;
        }

        try {
            $eventID = $this->getLastEventID();
        } catch (\PDOException $e) {
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile recupeare l\'ultimo evento.');*/

            $this->db->rollBack();

            return false;
        }

        /** @var $associazioni int[] */
        foreach ($associazioni as $ass) {
            try {
                $this->addPropose($eventID, $ass);
            } catch (\PDOException $e) {
                /*$this->setErrorMessage('PDOException, check errorInfo.',
                    'Impossibile associare le associazioni.');*/

                $this->db->rollBack();

                return false;
            }
        }
        $this->db->commit();

        return true;
    }

    /**
     * @param $update
     * @return bool
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
                E.revisionato = :revisionato
            WHERE E.idEvento = :idEvento
        ');
        $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':titolo', $titolo, \PDO::PARAM_STR);
        $sth->bindParam(':descrizione', $descrizione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteCreazione', $istanteCreazione, \PDO::PARAM_STR);
        $sth->bindParam(':istanteInizio', $istanteInizio, \PDO::PARAM_STR);
        $sth->bindParam(':istanteFine', $istanteFine, \PDO::PARAM_STR);
        $sth->bindParam(':revisionato', $revisionato, \PDO::PARAM_INT);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare l\'evento.',
                $this->db->errorInfo());*/

            $this->db->rollBack();

            return false;
        }

        try {
            $this->deleteOldProposes($eventID);
        } catch (\PDOException $e) {
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare le precedenti associazioni collegate all\'evento.');*/

            $this->db->rollBack();

            return false;
        }

        try {
            $this->createProposes($eventID, $update);
        } catch (\PDOException $e) {
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare le precedenti associazioni collegate all\'evento.');*/

            $this->db->rollBack();

            return false;
        }

        $this->db->commit();

        return true;
    }

    /**
     * @param $eventID
     * @return bool
     */
    public function deleteEvent($eventID): bool
    {
        $this->db->beginTransaction();

        try {
            $this->deleteFromProposes($eventID);
        } catch (\PDOException $e) {
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare le associazioni collegate all\'evento.');*/

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
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare l\'evento.',
                $this->db->errorInfo());*/

            $this->db->rollBack();

            return false;
        }

        $this->db->commit();

        return true;
    }

    /**
     * @param $eventID
     * @param $data
     * @return bool
     */
    public function updatePage($eventID, $data): bool
    {
        $sth = $this->db->prepare('
            UPDATE Evento E
            SET E.pagina = :pagina,
              E.immagine = :immagine
            WHERE E.idEvento = :eventID
        ');
        $sth->bindParam(':pagina', $data['pagina'], \PDO::PARAM_STR);
        $sth->bindParam(':eventID', $eventID, \PDO::PARAM_INT);
        $sth->bindParam(':immagine', $data['immagine'], \PDO::PARAM_STR);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare la pagina.',
                $this->db->errorInfo());*/

            return false;
        }

        return true;
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
        $eventsCount = count($events);

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
     * @param $eventID
     * @param $associationID
     * @return bool
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

    private function deleteOldProposes($eventID): bool
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

    private function createProposes($eventID, $event): bool
    {
        $associationsIds = $event['associazioni'];

        /** @var $associationsIds int[] */
        foreach ($associationsIds as $associationsID) {
            $sth = $this->db->prepare('
                INSERT INTO Proporre (
                    idEvento, idAssociazione
                )
                VALUES (
                    :idEvento, :idAssociazione
                )
            ');
            $sth->bindParam(':idEvento', $eventID, \PDO::PARAM_INT);
            $sth->bindParam(':idAssociazione', $associationsID, \PDO::PARAM_INT);


            try {
                $sth->execute();
            } catch (\PDOException $e) {
                throw $e;
            }
        }

        return true;
    }
}