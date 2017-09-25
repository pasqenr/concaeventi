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
     * @return array
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
     * @param $sponsorID
     * @return array
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
     * @param $data
     * @return bool
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
     * @param $sponsorID
     * @param $data
     * @return bool
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
     * @param $sponsorID
     * @return bool
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