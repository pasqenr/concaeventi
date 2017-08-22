<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use \App\Helpers\SessionHelper;

class PanelController extends Controller
{
    public function panel(RequestInterface $request, ResponseInterface $response, $args)
    {
        $user = SessionHelper::auth($this, $response, SessionHelper::EDITORE);

        if (empty($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        return $this->render($response, 'front/panel.twig', [
            'utente' => $user
        ]);
    }
}