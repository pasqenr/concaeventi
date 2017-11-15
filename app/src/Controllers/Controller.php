<?php

namespace App\Controllers;

use Slim\Container;
use \App\Helpers\SessionHelper;

/**
 * Class Controller
 * @package App\Controllers
 *
 * @property \Slim\Container container
 */
class Controller
{
    private $container;

    protected $user;
    protected $session;

    /**
     * @param Container $container
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->session = new SessionHelper();
        $this->user = $this->session->getUser();
    }

    /**
     * @param $response
     * @param string $template
     * @param array $data
     */
    public function render(
        $response,
        $template,
        array $data = []
    ): void {
        /** @noinspection PhpUndefinedFieldInspection */
        $this->container->view->render($response, $template, $data);
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws \Interop\Container\Exception\ContainerException
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
}
