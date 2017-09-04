<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Container;

class Controller
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array $err Contains useful information for the user when an error appears.
     */
    private $err = [
        'message' => '',
        'debugMessage' => '',
        'errorInfo' => ''
    ];

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param ResponseInterface $response
     * @param string $template
     * @param array $data
     */
    public function render(
        ResponseInterface $response,
        $template,
        array $data = []
    ) {
        $this->container->view->render($response, $template, $data);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->container->get($name);
    }

    public function __set($name, $value)
    {

    }

    public function __isset($name)
    {

    }

    public function setErrorMessage($debugMessage = 'Errore nell\'elaborazione dei dati.',
                                    $message = '',
                                    $errorInfo = ['', '', ''])
    {
        $this->err['message'] = $message;
        $this->err['debugMessage'] = debug_backtrace()[1]['function'] . ': ' . $debugMessage;
        $this->err['errorInfo'] = $errorInfo[2];
    }

    public function getErrorMessage()
    {
        return $this->err;
    }
}
