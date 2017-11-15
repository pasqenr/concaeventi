<?php

namespace App\Models;

use \App\Helpers\ErrorHelper;

/**
 * Class FundingModel
 * @package App\Models
 *
 * @property ErrorHelper errorHelper
 */
class FundingModel extends Model
{
    /**
     * Return the all the funding of the event identified by $eventID
     * sponsored by the sponsor identified by $sponsorID.
     *
     * @param $eventID int A valid event identifier.
     * @param $sponsorID int A valid sponsor identifier.
     * @return array The funding to the event $eventID by the sponsor
     *         $sponsorID.
     * @throws \PDOException
     */
    public function getFunding($eventID, $sponsorID): array
    {
        $sth = $this->db->prepare('
            SELECT F.idEvento, F.idSponsor, F.importo, F.dataFinanziamento, E.titolo, S.nome
            FROM Finanziamento F
                JOIN Evento E
                USING (idEvento)
                JOIN Sponsor S
                USING (idSponsor)
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

    /**
     * Create a new funding using $data as values.
     *
     * @param $data array The values to insert in the new funding.
     * @return bool TRUE if the funding is created, FALSE otherwise.
     */
    public function createFunding($data): bool
    {
        $sponsorID = $data['idSponsor'];
        $eventID = $data['idEvento'];
        $amount = $data['importo'];

        if ($amount !== '') {
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
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Creazione finanziamento: errore nell\'elaborazione dei dati.');

            return false;
        }

        return true;
    }

    /**
     * Update an already existent funding identified by $eventID and $sponsorID
     * with the values in $data.
     *
     * @param $eventID int The event identifier.
     * @param $sponsorID int The sponsor identifier.
     * @param $data array The new values.
     * @return bool TRUE if the funding was updated, FALSE otherwise.
     */
    public function updateFunding($eventID, $sponsorID, $data): bool
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

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare il finanziamento.');

            return false;
        }

        return true;
    }

    /**
     * Delete the event identified by $eventID and $sponsorID.
     *
     * @param $eventID int The event identifier.
     * @param $sponsorID int The sponsor identifier.
     * @return bool TRUE if the funding was deleted, FALSE otherwise.
     */
    public function deleteFunding($eventID, $sponsorID): bool
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
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare il finanziamento.');

            return false;
        }

        return true;
    }
}