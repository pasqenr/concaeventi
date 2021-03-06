<?php

/** @var $container \Slim\Container */
if (true === $container->settings['Session']['enable']) {
    // session must be initialized (by another middleware)
    // before adding this Twig extension
    $app->add(function (
        Psr\Http\Message\RequestInterface $request,
        Psr\Http\Message\ResponseInterface $response,
        callable $next
    ) use ($container) {
        $container->view->addExtension(new App\TwigExtensions\FlashMessages(
            $container->flash
        ));

        return $next($request, $response);
    });

    $app->add($container->csrf);
    $app->add(new RKA\SessionMiddleware($container->settings['Session']));
}
