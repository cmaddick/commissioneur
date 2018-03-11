<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'config.php';

// Create the application
$app = new \Slim\App(['settings' => $config]);

// Get container
$container = $app->getContainer();

// Register template viewing component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('public/templates', [
        'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

// Routing functions
$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");

    return $response;
});

// Render Twig template in route
$app->get('/login', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html');
});

$app->run();
