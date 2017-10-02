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
    const ALL = 0;
    const EDITORE = 1;
    const PUBLISHER = 2;
    const DIRETTORE = 4;
    const AMMINISTRATORE = 8;
    
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

    /**
     * @param int $level
     * @return bool
     */
    public function auth($level = self::ALL): bool
    {
        if (!$this->isLogged()) {
            return false;
        }

        $userLevel = $this->session->get('ruolo') ?? 0;

        return $userLevel >= $level;
    }

    /**
     * @param $user
     */
    public function setUserData(&$user)
    {
        $this->session->idUtente      = $user['idUtente'];
        $this->session->nomeUtente    = $user['nome'];
        $this->session->cognomeUtente = $user['cognome'];
        $this->session->email         = $user['email'];
        $this->session->ruolo         = $this->ruoloFromString($user['ruolo']);
    }

    /**
     * @return array
     */
    public function getUser(): array
    {
        if (!$this->isLogged()) {
            return [];
        }

        $user['idUtente'] = $this->session->idUtente;
        $user['nome']     = $this->session->nomeUtente;
        $user['cognome']  = $this->session->cognomeUtente;
        $user['email']    = $this->session->email;
        $user['ruolo']    = $this->ruoloToString($this->session->ruolo);

        return $user;
    }

    /**
     * @param string $level
     * @return int
     * @throws \InvalidArgumentException
     */
    public function ruoloFromString(string $level = 'All'): int
    {
        switch ($level) {
            case 'Amministratore':
                return self::AMMINISTRATORE;
                break;
            case 'Direttore':
                return self::DIRETTORE;
                break;
            case 'Publisher':
                return self::PUBLISHER;
                break;
            case 'Editore':
                return self::EDITORE;
                break;
            case 'All';
                self::ALL;
                break;

            default:
                throw new \InvalidArgumentException('Wrong level name');
        }

        throw new \InvalidArgumentException('Wrong level name');
    }

    /**
     * @param int $level
     * @return string
     */
    public function ruoloToString($level = self::ALL)
    {
        switch ($level) {
            case self::AMMINISTRATORE:
                return 'Amministratore';
                break;
            case self::DIRETTORE:
                return 'Direttore';
                break;
            case self::PUBLISHER:
                return 'Publisher';
                break;
            case self::EDITORE:
                return 'Editore';
                break;
            case self::ALL;
                return 'All';
                break;

            default:
                throw new \InvalidArgumentException('Wrong level code');
        }
    }

    /**
     *
     */
    public function destroySession()
    {
        Session::destroy();
    }
}