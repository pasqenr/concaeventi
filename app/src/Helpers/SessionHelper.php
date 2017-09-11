<?php

namespace App\Helpers;
use RKA\Session;

/**
 * Class used to provide helper functions to manage users' sessions. It's intended to have
 * static methods because the session is passed to the functions.
 *
 * Class SessionHelper
 * @package App\Helpers
 */
class SessionHelper {
    const ALL = '';
    const EDITORE = 'Editore';
    const PUBLISHER = 'Publisher';
    const DIRETTORE = 'Direttore';
    const AMMINISTRATORE = 'Amministratore';
    
    private $session;

    public function __construct()
    {
        $this->session = new \RKA\Session();
    }

    /**
     * Check if the idUtente variable in the session is setted.
     *
     * @param $session \RKA\Session The user session.
     * @return bool True if the user is logged, false otherwise.
     */
    public function isLogged(): bool
    {
        return $this->session->get('idUtente') !== null;
    }

    public function auth($level = self::ALL): bool
    {
        if (!$this->isLogged()) {
            return false;
        }

        if ($level === self::ALL) {
            return true;
        }

        switch ($this->session->get('ruolo')) {
            case self::AMMINISTRATORE:
                break;
            case self::DIRETTORE:
                break;
            case self::PUBLISHER:
                break;
            case self::EDITORE:
                break;
            case self::ALL;
                break;

            default:
                return false;
        }

        return true;
    }

    public function setUserData(&$user)
    {
        $this->session->idUtente      = $user['idUtente'];
        $this->session->nomeUtente    = $user['nome'];
        $this->session->cognomeUtente = $user['cognome'];
        $this->session->email         = $user['email'];
        $this->session->ruolo         = $user['ruolo'];
    }

    public function getUser(): array
    {
        if (!$this->isLogged()) {
            return [];
        }

        $user['idUtente'] = $this->session->idUtente;
        $user['nome']     = $this->session->nomeUtente;
        $user['cognome']  = $this->session->cognomeUtente;
        $user['email']    = $this->session->email;
        $user['ruolo']    = $this->session->ruolo;

        return $user;
    }

    public function destroySession()
    {
        Session::destroy();
    }
}