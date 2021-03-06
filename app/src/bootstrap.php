<?php

// ========== PATHS ==========

define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH',  ROOT_PATH.'/app');
define('VAR_PATH',  ROOT_PATH.'/var');
define('WWW_PATH',  ROOT_PATH.'/public');

// ========== PHP (static) ==========

// errors
error_reporting(-1);
ini_set('error_log', VAR_PATH.'/log/php-'.date('Y-m').'.log');

// charset
ini_set('default_charset', 'UTF-8');

// ========== COMPOSER ==========

require ROOT_PATH.'/vendor/autoload.php';

// ========== CONFIGURATION ==========

$config = require 'config.php';

// ========== PHP (from configuration) ==========

// language
date_default_timezone_set('Europe/Rome');
setlocale(LC_TIME, 'it_IT');

// time zone
date_default_timezone_set($config['PHP']['default_timezone']);

// errors
ini_set('display_errors',         $config['PHP']['display_errors']);
ini_set('display_startup_errors', $config['PHP']['display_startup_errors']);
ini_set('log_errors',             $config['PHP']['log_errors']);

unset($config['PHP']);

// ========== SLIM ==========

$app = new Slim\App(['settings' => $config]);
$app->add(new \Slim\Csrf\Guard);
$container = $app->getContainer();

require 'dependencies.php';
require 'middlewares.php';
require 'routes.php';

// Pass datas to view
$container->view['config'] = array_merge($config['App'], $config['Security']);

// Error handler
if (!$container->settings['displayErrorDetails']) {
    $container['errorHandler'] = function ($container) {
        return function (
            Psr\Http\Message\RequestInterface $request,
            Psr\Http\Message\ResponseInterface $response,
            Exception $exception
        ) use ($container) {
            $container->logger->error($exception);

            return $container->view->render(
                $response->withStatus(500),
                'errors/error.twig'
            );
        };
    };
}

$app->run();
