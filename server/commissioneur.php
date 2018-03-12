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

$container['db'] = function ($container) {
    $settings = $container['settings'];
    $pdo = new PDO('mysql:host=localhost;dbname=commission', $settings['db']['user'], $settings['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// Routing functions
$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");

    return $response;
});

$app->get('/login', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html');
})->setName('login');

$app->get('/signup', function ($request, $response, $args) {
    return $this->view->render($response, 'signup.html', [
        'title' => 'Sign up'
    ]);
});

$app->post('/signup', function(Request $request, Response $response) {

    $allPostPutVars = $request->getParsedBody();

    $email = $allPostPutVars['inputEmail'];
    $displayname = $allPostPutVars['inputDisplayName'];
    $password = $allPostPutVars['inputPassword'];
    $repassword = $allPostPutVars['inputRePassword'];

    $pdo = $this->db;

    $stmt = $pdo->prepare('SELECT * FROM users WHERE Email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if (!$row) {
        $userID = mt_rand(100000000, 999999999);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE UserID = ?');
        $stmt->execute([$userID]);
        $row = $stmt->fetch();

        if (!$row) {
            $stmt = $pdo->prepare('INSERT INTO `users` (`UserID`, `Email`, `Password`, `DisplayName`) VALUES (:userid, :email, :password, :displayname)');
            $stmt->bindParam(':userid', $userID);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':displayname', $displayname);
            $stmt->execute();

            $router = $this->router;
            return $response->withStatus(303)->withHeader('Location', $router->pathFor('signupsuccess'));
        }
    } else {

    }
});

$app->get('/signupsuccess', function ($request, $response, $args) {
    return $this->view->render($response, 'signupsuccess.html');
})->setName('signupsuccess');

$app->get('/submission/{submissionid}', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html');
});

$app->get('/profile/{profileid}', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html');
});

$app->run();
