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
        $authorized = $this->session->auth();

        if ($authorized) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/login.twig', [
            'utente' => $this->user
        ]);
    }

    public function doLogin(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $arguments = $request->getParsedBody();
        $email     = trim($arguments['email']);
        $password  = trim($arguments['password']);

        $user = $this->getUserByEmail($email);

        if (!$user) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        $passwordMatch = password_verify($password, $user['password']);

        if (!$passwordMatch) {
            /*return $response->withRedirect($this->router->pathFor('error'));*/
            $this->setErrorMessage('Password don\'t match.',
                'Email o password errati.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->getErrorMessage()
            ]);
        }

        $this->session->setUserData($user);

        return $response->withRedirect($this->router->pathFor('home'));
    }

    public function logout(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $this->session->destroySession();

        return $response->withRedirect($this->router->pathFor('home'));
    }

    private function getUserByEmail($email)
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