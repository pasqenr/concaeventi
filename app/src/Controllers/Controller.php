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
        'code' => -1
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

    public function setErrorMessage($debugMessage = '', $message = '', $code = -1)
    {
        $this->err['message'] = $message;
        $this->err['debugMessage'] = $debugMessage;
        $this->err['code'] = $code;
    }

    public function getErrorMessage()
    {
        return $this->err;
    }
}
