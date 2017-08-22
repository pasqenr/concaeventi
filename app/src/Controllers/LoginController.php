<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class LoginController extends Controller
{
    public function login(RequestInterface $request, ResponseInterface $response)
    {
        $session = new \RKA\Session();
        $user    = [];

        if (SessionHelper::isLogged($session)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        return $this->render($response, 'front/login.twig', [
            'utente' => $user
        ]);
    }

    public function doLogin(RequestInterface $request, ResponseInterface $response)
    {
        $session = new \RKA\Session();

        $arguments = $request->getParsedBody();
        $email     = trim($arguments['email']);
        $password  = trim($arguments['password']);

        $sth = $this->db->prepare("
            SELECT U.idUtente, U.password, U.nome, U.cognome, U.email, U.ruolo
            FROM Utente U
            WHERE U.email LIKE :email
        ");
        $sth->bindParam(':email', $email, \PDO::PARAM_STR);
        $sth->execute();

        $user = $sth->fetch();

        $good = password_verify($password, $user['password']);

        if (!$good) {
            return $response->withRedirect($this->router->pathFor('error'));
        }

        if ($user) {
            SessionHelper::setSessionUser($session, $user);
        }

        return $response->withRedirect($this->router->pathFor('home'));
    }

    public function logout(RequestInterface $request, ResponseInterface $response)
    {
        \RKA\Session::destroy();

        return $response->withRedirect($this->router->pathFor('home'));
    }
}