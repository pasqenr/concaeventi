<?php

namespace App\Models;

/**
 * Class FundingModel
 * @package App\Models
 */
class FundingModel extends Model
{
    /**
     * @param $eventID
     * @param $sponsorID
     * @return array
     * @throws \PDOException
     */
    public function getFunding($eventID, $sponsorID): array
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

    /**
     * @param $data
     * @return bool
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
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Creazione finanziamento: errore nell\'elaborazione dei dati.');*/

            return false;
        }

        return true;
    }

    /**
     * @param $eventID
     * @param $sponsorID
     * @param $data
     * @return bool
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
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare il finanziamento.');*/

            return false;
        }

        return true;
    }

    /**
     * @param $eventID
     * @param $sponsorID
     * @return bool
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
            /*$this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare il finanziamento.');*/

            return false;
        }

        return true;
    }
}