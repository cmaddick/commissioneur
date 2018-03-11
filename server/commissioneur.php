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

$app->get('/login', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html');
});

$app->get('/signup', function ($request, $response, $args) {
    return $this->view->render($response, 'signup.html', [
        'title' => 'Sign up'
    ]);
});

$app->post('/signup', function(Request $request, Response $response) {
    $email = $request->getAttribute('inputEmail');
    $displayname = $request->getAttribute('inputDisplayName');
    $password = $request->getAttribute('inputPassword');
    $repassword = $request->getAttribute('inputRePassword');
});

$app->get('/submission/{submissionid}', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html');
});

$app->get('/profile/{profileid}', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html');
});

$app->run();
