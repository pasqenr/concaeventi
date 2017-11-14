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

    /**
     * SessionHelper constructor.
     */
    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * Check if the idUtente variable in the session is set.
     *
     * @return bool TRUE if the user is logged, FALSE otherwise.
     */
    public function isLogged(): bool
    {
        return $this->session->get('idUtente') !== null;
    }

    /**
     * Check if the user is authorized to the level defined by $level.
     *
     * @param int $level The level of authorization.
     * @return bool TRUE if the user is authorized, FALSE otherwise.
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
     * Set the in-memory session information about the logged user.
     *
     * @param array $user The logged user data.
     * @throws \InvalidArgumentException
     */
    public function setUserData(&$user)
    {
        $this->session->set('idUtente', $user['idUtente']);
        $this->session->set('nomeUtente', $user['nome']);
        $this->session->set('cognomeUtente', $user['cognome']);
        $this->session->set('email', $user['email']);
        $this->session->set('ruolo', $this->roleFromString($user['ruolo']));
    }

    /**
     * Return an array with the user data stored in the session memory.
     *
     * @return array The user data.
     * @throws \InvalidArgumentException
     */
    public function getUser(): array
    {
        if (!$this->isLogged()) {
            return [];
        }

        $user['idUtente'] = $this->session->get('idUtente');
        $user['nome']     = $this->session->get('nomeUtente');
        $user['cognome']  = $this->session->get('cognomeUtente');
        $user['email']    = $this->session->get('email');
        $user['ruolo']    = $this->roleToString($this->session->get('ruolo'));

        return $user;
    }

    /**
     * Return the role const from a string.
     *
     * @param string $level A string that represent a level.
     * @return int The value for the role.
     * @throws \InvalidArgumentException
     */
    public function roleFromString(string $level = 'all'): int
    {
        $level = strtolower(trim($level));

        switch ($level) {
            case 'amministratore':
                return self::AMMINISTRATORE;
                break;
            case 'direttore':
                return self::DIRETTORE;
                break;
            case 'publisher':
                return self::PUBLISHER;
                break;
            case 'editore':
                return self::EDITORE;
                break;
            case 'all';
                self::ALL;
                break;

            default:
                throw new \InvalidArgumentException('Wrong level name');
        }

        throw new \InvalidArgumentException('Wrong level name');
    }

    /**
     * Return the string associated to the value in $level.
     *
     * @param int $level The value that represent a level. Use the internal
     *        const constants.
     * @return string The string associated to the $level.
     * @throws \InvalidArgumentException
     */
    public function roleToString($level = self::ALL)
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
     * Destroy the user session.
     */
    public function destroySession()
    {
        Session::destroy();
    }
}