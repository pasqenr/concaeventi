<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Container;
use \App\Helpers\SessionHelper;

/**
 * Class Controller
 * @package App\Controllers
 *
 * @property
 */
class Controller
{
    /**
     * @var Container
     */
    private $container;

    protected $user;
    protected $session;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->session = new SessionHelper();
        $this->user = $this->session->getUser();
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
