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

// Set up the database connection and add it as a slim container
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

$app->get('/home', function ($request, $response, $args) {
    return $this->view->render($response, 'home.html', [
        'session' => $_SESSION
    ]);
})->setName('home');

$app->get('/login', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html');
})->setName('login');

$app->post('/login', function ($request, $response, $args) {
    $allPostPutVars = $request->getParsedBody();

    $email = $allPostPutVars['inputEmail'];
    $password = $allPostPutVars['inputPassword'];

    $pdo = $this->db;

    $stmt = $pdo->prepare('SELECT * FROM users WHERE Email = ? AND Password = ?');
    $stmt->execute([$email, $password]);
    $row = $stmt->fetch();

    if(!$row) {
        $userID = $row['UserID'];
        $displayName = $row['DisplayName'];

        $_SESSION['UserID'] = $userID;
        $_SESSION['DisplayName'] = $displayName;

        $router = $this->router;
        return $response->withStatus(303)->withHeader('Location', $router->pathFor('home'));
    }
});

$app->get('/signup', function ($request, $response, $args) {
    return $this->view->render($response, 'signup.html', [
        'title' => 'Sign up'
    ]);
});

$app->post('/signup', function(Request $request, Response $response) {

    // Get form data
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

// Static site image route handling
$app->get('/resources/images/{data:\w+}', function($request, $response, $args) {
    $data = $args['data'];
    $image = @file_get_contents("http://localhost/public/resources/images/$data");
    if($image === FALSE) {
        $handler = $this->notFoundHandler;
        return $handler($request, $response);
    }

    $response->write($image);
    return $response->withHeader('Content-Type', FILEINFO_MIME_TYPE);
});

$app->run();
