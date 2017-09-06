<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;
use \RKA\Session;

/**
 * @property Router router
 * @property \PDO db
 */
class LoginController extends Controller
{
    public function login(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $session = new Session();
        $user    = [];

        if (SessionHelper::isLogged($session)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/login.twig', [
            'utente' => $user
        ]);
    }

    public function doLogin(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $session = new Session();

        $arguments = $request->getParsedBody();
        $email     = trim($arguments['email']);
        $password  = trim($arguments['password']);

        $sth = $this->db->prepare('
            SELECT U.idUtente, U.password, U.nome, U.cognome, U.email, U.ruolo
            FROM Utente U
            WHERE U.email LIKE :email
        ');
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

    public function logout(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        Session::destroy();

        return $response->withRedirect($this->router->pathFor('home'));
    }
}