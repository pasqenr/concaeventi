<?php

namespace App\Helpers;

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

    /**
     * Check if the idUtente variable in the session is setted.
     *
     * @param $session \RKA\Session The user session.
     * @return bool True if the user is logged, false otherwise.
     */
    public static function isLogged(&$session)
    {
        return isset($session->idUtente);
    }

    /**
     * @param $session \RKA\Session The user session.
     * @param $user array The user array with the following members: idUtente, nome, cognome, email and ruolo.
     */
    public static function setSessionUser(&$session, &$user)
    {
        $session->idUtente      = $user['idUtente'];
        $session->nomeUtente    = $user['nome'];
        $session->cognomeUtente = $user['cognome'];
        $session->email         = $user['email'];
        $session->ruolo         = $user['ruolo'];
    }

    /**
     * @param $session \RKA\Session The user session.
     * @param $user array The user array is filled with the following members: idUtente, nome, cognome, email and ruolo.
     */
    public static function setSessionArray(&$session, &$user)
    {
        $user['idUtente'] = $session->idUtente;
        $user['nome']     = $session->nomeUtente;
        $user['cognome']  = $session->cognomeUtente;
        $user['ruolo']    = $session->ruolo;
    }

    public static function auth($that, $response, $level = SessionHelper::ALL)
    {
        $session = new \RKA\Session();
        $user = [];

        if (SessionHelper::isLogged($session)) {
            SessionHelper::setSessionArray($session, $user);
        } else {
            if ($level !== SessionHelper::ALL) {
                return $user;
            }
        }

        if ($level === SessionHelper::ALL) {
            return $user;
        }

        switch ($user['ruolo']) {
            case SessionHelper::DIRETTORE:
                break;
            case SessionHelper::PUBLISHER:
                break;
            case SessionHelper::EDITORE:
                break;
            case SessionHelper::ALL;
                break;

            default:
                return $user;
        }

        return $user;
    }
}