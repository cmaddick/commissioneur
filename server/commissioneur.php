<?php

session_start();
error_reporting(E_ERROR | E_PARSE);

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\UploadedFile;

require 'vendor/autoload.php';
require 'config.php';
require 'models/Submission.php';
require 'models/User.php';

// Create the application
$app = new \Slim\App(['settings' => $config]);

// Get container
$container = $app->getContainer();

// Add session management middleware
$app->add(new \Slim\Middleware\Session([
    'name' => 'dummy_session',
    'autorefresh' => true,
    'lifetime' => '24 hours'
]));

// Add csrf protection middleware
$app->add(new \Slim\Csrf\Guard);

$container['csrf'] = function ($c) {
    return new \Slim\Csrf\Guard;
};

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
    $pdo = new PDO('mysql:host=' . $settings['db']['host'] .';dbname=' . $settings['db']['dbname'], $settings['db']['user'], $settings['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// Register session middleware to app
$container['session'] = function ($c) {
    return new \SlimSession\Helper;
};

// Set file upload directory
$container['upload_directory'] = __DIR__ . '/public/resources/usercontent';

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

// the browse page is essentially the home page
$app->get('/browse', function ($request, $response, $args) {
    return $this->view->render($response, 'home.html', [
        'session' => $_SESSION
    ]);
})->setName('browse');

// router for shop page
$app->get('/shop', function ($request, $response, $args) {
    return $this->view->render($response, 'shop.html', [
        'session' => $_SESSION
    ]);
})->setName('shop');

// router for commissions page
$app->get('/commissions', function ($request, $response, $args) {
    return $this->view->render($response, 'commissions.html', [
        'session' => $_SESSION
    ]);
})->setName('commissions');

$app->get('/login', function ($request, $response, $args) {
    // CSRF token name and value
    $nameKey = $this->csrf->getTokenNameKey();
    $valueKey = $this->csrf->getTokenValueKey();
    $name = $request->getAttribute($nameKey);
    $value = $request->getAttribute($valueKey);

    $csrfKeysValues = [
        'nameKey' => $nameKey,
        'name' => $name,
        'valueKey' => $valueKey,
        'value' => $value
    ];

    return $this->view->render($response, 'login.html', [
        'title' => 'Login',
        'session' => $_SESSION,
        'csrf' => $csrfKeysValues
    ]);
})->setName('login');

$app->post('/login', function ($request, Response $response, $args) {
    $allPostPutVars = $request->getParsedBody();

    $email = $allPostPutVars['inputEmail'];
    $password = $allPostPutVars['inputPassword'];

    $pdo = $this->db;

    if ($user = User::login_user($pdo, $email, $password)){
        $router = $this->router;
        return $response->withStatus(303)->withHeader('Location', $router->pathFor('home'));
    } else {
        return $response->withStatus(404);
    }
});

$app->get('/signup', function ($request, $response, $args) {
    // CSRF token name and value
    $nameKey = $this->csrf->getTokenNameKey();
    $valueKey = $this->csrf->getTokenValueKey();
    $name = $request->getAttribute($nameKey);
    $value = $request->getAttribute($valueKey);

    $csrfKeysValues = [
        'nameKey' => $nameKey,
        'name' => $name,
        'valueKey' => $valueKey,
        'value' => $value
    ];

    return $this->view->render($response, 'signup.html', [
        'title' => 'Sign up',
        'csrf' => $csrfKeysValues
    ]);
});

$app->post('/signup', function(Request $request, Response $response) {

    // Get form data
    $allPostPutVars = $request->getParsedBody();

    $email = $allPostPutVars['inputEmail'];
    $displayName = $allPostPutVars['inputDisplayName'];
    $password = $allPostPutVars['inputPassword'];
    $rePassword = $allPostPutVars['inputRePassword'];

    $pdo = $this->db;

    if (User::register_user($pdo, $email, $displayName, $password, $rePassword)) {
        $router = $this->router;
        return $response->withStatus(303)->withHeader('Location', $router->pathFor('signupsuccess'));
    } else {
        return $response->withStatus(404);
    }

});

$app->get('/signupsuccess', function ($request, $response, $args) {
    return $this->view->render($response, 'signupsuccess.html');
})->setName('signupsuccess');

$app->get('/submission/{submissionid}', function ($request, $response, $args) {
    // Get submission from database
    $pdo = $this->db;

    if ($submission = Submission::get_submission($args['submissionid'], $pdo)) {
        return $this->view->render($response, 'submission.html', [
            'session' => $_SESSION,
            'submission' => $submission->get_submission_array()
        ]);
    }
})->setName('submission2');; //change name when database is working properly

// temp submission router for developing submissions page.
$app->get('/submission', function ($request, $response, $args) {
    return $this->view->render($response, 'submission.html');
})->setName('submission');

$app->get('/profile/{profileid}', function ($request, $response, $args) {
    return $this->view->render($response, 'profile.html');
})->setName('profile');;

$app->get('/logout', function (Request $request, Response $response){
    session_destroy();

    $router = $this->router;
    return $response->withStatus(303)->withHeader('Location', $router->pathFor('home'));
});

$app->get('/upload', function ($request, $response, $args) {
    // CSRF token name and value
    $nameKey = $this->csrf->getTokenNameKey();
    $valueKey = $this->csrf->getTokenValueKey();
    $name = $request->getAttribute($nameKey);
    $value = $request->getAttribute($valueKey);

    $csrfKeysValues = [
        'nameKey' => $nameKey,
        'name' => $name,
        'valueKey' => $valueKey,
        'value' => $value
    ];

    return $this->view->render($response, 'upload.html', [
        'session' => $_SESSION,
        'csrf' => $csrfKeysValues
    ]);
});

$app->post('/upload', function(Request $request, Response $response) {

    $uploadDirectory = $this->get('upload_directory');

    // Get form data
    $allPostPutVars = $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();

    $title = $allPostPutVars['inputTitle'];
    $description = $allPostPutVars['inputDescription'];
    $uploadedFile = $uploadedFiles['inputFile'];

    // Find submission type
    if ($allPostPutVars['type'] === 'art' || $allPostPutVars['type'] === 'crafts' || $allPostPutVars['type'] === 'photo') {
        $type = 'image';
    } elseif ($allPostPutVars === 'music') {
        $type = 'audio';
    } else {
        $type = 'video';
    }

    $pdo = $this->db;

    $submission = Submission::save_new_submission($pdo, $title, $type, $description, $uploadedFile, $uploadDirectory);

    if($submission) {
        $router = $this->router;
        return $response->withStatus(303)->withHeader('Location', $router->pathFor('submission', ['submissionid' => $submissionID]));
    } else {
        return $response->withStatus(404);
    }
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
