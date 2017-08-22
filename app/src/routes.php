<?php

$app->get('/', 'App\Controllers\FrontController:home')
    ->setName('home');

$app->get('/error/', function ($request, $response, $args) {
    return $this->render($response, 'events/error.twig');
});

$app->get('/login/', 'App\Controllers\LoginController:login')
    ->setName('login')
    ->add($container->get('csrf'));

$app->post('/login/', 'App\Controllers\LoginController:doLogin')
    ->setName('doLogin')
    ->add($container->get('csrf'));

$app->get('/logout/', 'App\Controllers\LoginController:logout')
    ->setName('logout');

$app->get('/panel/', 'App\Controllers\PanelController:panel')
    ->setName('panel');

$app->get('/page/{id}', 'App\Controllers\EventController:showPage')
    ->setName('page');

$app->get('/events/', 'App\Controllers\EventController:showEvents')
    ->setName('events');

$app->get('/associations/', 'App\Controllers\AssociationController:showAll')
    ->setName('associations');

/* Associations */

$app->get('/associations/create', 'App\Controllers\AssociationController:create')
    ->setName('associationsCreate')
    ->add($container->get('csrf'));

$app->post('/associations/create', 'App\Controllers\AssociationController:doCreate')
    ->setName('associationsDoCreate')
    ->add($container->get('csrf'));

$app->get('/associations/edit/{id}', 'App\Controllers\AssociationController:edit')
    ->setName('associationsEdit')
    ->add($container->get('csrf'));

$app->post('/associations/edit/{id}', 'App\Controllers\AssociationController:doEdit')
    ->setName('associationsDoEdit')
    ->add($container->get('csrf'));

$app->get('/associations/delete/{id}', 'App\Controllers\AssociationController:delete')
    ->setName('associationsDelete')
    ->add($container->get('csrf'));

$app->post('/associations/delete/{id}', 'App\Controllers\AssociationController:doDelete')
    ->setName('associationsDoDelete')
    ->add($container->get('csrf'));

/* Events */

$app->get('/events/edit/{id}', 'App\Controllers\EventController:edit')
    ->setName('eventsEdit')
    ->add($container->get('csrf'));

$app->post('/events/edit/{id}', 'App\Controllers\EventController:doEdit')
    ->setName('eventsDoEdit')
    ->add($container->get('csrf'));

$app->get('/events/create/', 'App\Controllers\EventController:create')
    ->setName('eventsCreate')
    ->add($container->get('csrf'));

$app->post('/events/create/', 'App\Controllers\EventController:doCreate')
    ->setName('eventsDoCreate')
    ->add($container->get('csrf'));

$app->get('/events/delete/{id}', 'App\Controllers\EventController:delete')
    ->setName('eventsDelete')
    ->add($container->get('csrf'));

$app->post('/events/delete/{id}', 'App\Controllers\EventController:doDelete')
    ->setName('eventsDoDelete')
    ->add($container->get('csrf'));

$app->get('/events/page/{id}', 'App\Controllers\EventController:page')
    ->setName('eventsPage')
    ->add($container->get('csrf'));

$app->post('/events/page/{id}', 'App\Controllers\EventController:doPage')
    ->setName('eventsDoPage')
    ->add($container->get('csrf'));

// Page not found handler
$container['notFoundHandler'] = function ($container) {
    return function (
        Psr\Http\Message\RequestInterface $request,
        Psr\Http\Message\ResponseInterface $response,
        Slim\Exception\NotFoundException $exception = null
    ) use ($container) {
        return $container->view->render(
            $response->withStatus(404),
            'errors/not-found.twig'
        );
    };
};
