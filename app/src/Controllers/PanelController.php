<?php

namespace App\Controllers;

use \App\Helpers\SessionHelper;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Router;

/**
 * @property Router router
 * @property \PDO db
 */
class PanelController extends Controller
{
    public function panel(/** @noinspection PhpUnusedParameterInspection */
        Request $request, Response $response, $args)
    {
        $authorized = $this->session->auth(SessionHelper::EDITORE);

        if (!$authorized) {
            return $response->withRedirect($this->router->pathFor('auth-error'));
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->render($response, 'front/panel.twig', [
            'utente' => $this->user
        ]);
    }
}