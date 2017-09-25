<?php

namespace App\Controllers;

use \App\Helpers\ErrorHelper;
use \App\Models\UserModel;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;

/**
 * @property Router router
 * @property \PDO db
 */
class LoginController extends Controller
{
    private $userModel;
    private $errorHelper;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->errorHelper = new ErrorHelper();
        $this->userModel = new UserModel($this->db, $this->errorHelper);
    }

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
                'err' => $this->errorHelper->getErrorMessage()
            ]);
        }

        $passwordMatch = password_verify($password, $user['password']);

        if (!$passwordMatch) {
            /*return $response->withRedirect($this->router->pathFor('error'));*/
            $this->errorHelper->setErrorMessage('Password don\'t match.',
                'Email o password errati.');

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->render($response, 'errors/error.twig', [
                'utente' => $this->user,
                'err' => $this->errorHelper->getErrorMessage()
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
        return $this->userModel->getUserByEmail($email);
    }
}