<?php

namespace App\Models;

/**
 * Class UserModel
 * @package App\Models
 *
 * @property \PDO db
 */
class UserModel extends Model
{
    /**
     * @return array
     */
    public function getAllMembers(): array
    {
        $sth = $this->db->query('
            SELECT U.idUtente, U.nome, U.cognome
            FROM Utente U
        ');

        return $sth->fetchAll();
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getUserByEmail($email)
    {
        $sth = $this->db->prepare('
            SELECT U.idUtente, U.password, U.nome, U.cognome, U.email, U.ruolo
            FROM Utente U
            WHERE U.email LIKE :email
        ');
        $sth->bindParam(':email', $email, \PDO::PARAM_STR);

        try {
            $sth->execute();
        } catch (\PDOException $e) {
            $this->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'utente o errore generico.');
        }

        return $sth->fetch();
    }
}