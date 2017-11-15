<?php

namespace App\Models;

use \App\Helpers\ErrorHelper;

/**
 * Class UserModel
 * @package App\Models
 *
 * @property ErrorHelper errorHelper
 */
class UserModel extends Model
{
    /**
     * Return all the users.
     *
     * @return array The users.
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
     * Return the user with the (unique) email $email.
     *
     * @param $email string The user's email.
     * @return mixed The user with the email $email.
     * @throws \PDOException
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
            $this->errorHelper->setErrorMessage('PDOException, check errorInfo.',
                'Impossibile trovare l\'utente o errore generico.');

            throw $e;
        }

        return $sth->fetch();
    }
}