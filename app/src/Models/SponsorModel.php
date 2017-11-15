<?php

namespace App\Models;

use \App\Helpers\ErrorHelper;

/**
 * Class SponsorModel
 * @package App\Models
 *
 * @property ErrorHelper errorHelper
 */
class SponsorModel extends Model
{
    /**
     * Return all sponsor.
     *
     * @return array All the sponsor.
     */
    public function getSponsors(): array
    {
        $sth = $this->db->query('
            SELECT S.idSponsor, S.nome, S.logo
            FROM Sponsor S
        ');

        return $sth->fetchAll();
    }

    /**
     * Return the sponsor identified by $sponsorID.
     *
     * @param $sponsorID int A valid sponsor identifier.
     * @return array The sponsor identified by $sponsorID.
     */
    public function getSponsor($sponsorID): array
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

    /**
     * Create a new sponsor using $data as values.
     *
     * @param $data array The values to insert in the new sponsor.
     * @return bool TRUE if the sponsor is created, FALSE otherwise.
     */
    public function createSponsor($data): bool
    {
        $sponsorName = $data['nome'];
        $logo = $data['logo'];

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
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile creare lo sponsor.');

            return false;
        }

        return true;
    }

    /**
     * Update an already existent sponsor identified by $sponsorID with the
     * values in $data.
     *
     * @param $sponsorID int The sponsor identifier.
     * @param $data array The new values.
     * @return bool TRUE if the funding was updated, FALSE otherwise.
     */
    public function updateSponsor($sponsorID, $data): bool
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

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile modificare lo sponsor.');

            return false;
        }

        return true;
    }

    /**
     * Delete the event identified by $sponsorID.
     *
     * @param $sponsorID int The sponsor identifier.
     * @return bool TRUE if the funding was deleted, FALSE otherwise.
     */
    public function deleteSponsor($sponsorID): bool
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
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile eliminare lo sponsor.');

            return false;
        }

        return true;
    }
}