<?php

session_start();
error_reporting(E_ALL ^ E_WARNING); // suppress the php warnings

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\UploadedFile;

require 'vendor/autoload.php';
require 'config.php';

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
    $pdo = new PDO('mysql:host=localhost;dbname=commissioneur', $settings['db']['user'], $settings['db']['pass']);
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

//get router for login page
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

    $stmt = $pdo->prepare('SELECT * FROM users WHERE Email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if($row) {

        $dbPasswordHash = $row['PASSWORD'];

        if(password_verify($password, $dbPasswordHash)) {
            $userID = $row['UserID'];
            $displayName = $row['DisplayName'];
            $_SESSION['IsLoggedIn'] = 'true';
            $_SESSION['UserID'] = $userID;
            $_SESSION['DisplayName'] = $displayName;

            $router = $this->router;
            return $response->withStatus(303)->withHeader('Location', $router->pathFor('home'));
        }
    }
});

//get router for signup page
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
    $displayname = $allPostPutVars['inputDisplayName'];
    $password = $allPostPutVars['inputPassword'];
    $repassword = $allPostPutVars['inputRePassword'];

    $password = password_hash($password, PASSWORD_DEFAULT);

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

    // Get submission from database
    $pdo = $this->db;

    $stmt = $pdo->prepare('SELECT * FROM submissions WHERE SubmissionID = ?');
    $stmt->execute([$args['submissionid']]);
    $row = $stmt->fetch();

    if ($row) {
        $submission = [
            'title' => $row['ContentTitle'],
            'path' => $row['ContentPath'],
            'description' => $row['ContentDescription']
        ];

        return $this->view->render($response, 'submission.html', [
            'session' => $_SESSION,
            'submission' => $submission
        ]);
    }

})->setName('submission');;

$app->get('/profile/{profileid}', function ($request, $response, $args) {
    return $this->view->render($response, 'profile.html');
});

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
    $dbPath = "";

    // Get form data
    $allPostPutVars = $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();

    $submissionID = mt_rand(100000000, 999999999);
    $title = $allPostPutVars['inputTitle'];
    $type = $allPostPutVars['inputType'];
    $description = $allPostPutVars['inputDescription'];

    if ($type === "image") {
        $uploadDirectory = $uploadDirectory . '/images';
        $dbPath = "../public/resources/usercontent/images";
    } elseif ($type === "audio") {
        $uploadDirectory = $uploadDirectory . '/images';
        $dbPath = "../public/resources/usercontent/audio";
    }

    $pdo = $this->db;

    // Check if submissionID is unique
    $stmt = $pdo->prepare('SELECT * FROM submissions WHERE SubmissionID = ?');
    $stmt->execute([$submissionID]);
    $row = $stmt->fetch();

    if (!$row) {
        // Handle file upload
        $uploadedFile = $uploadedFiles['inputFile'];
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = moveUploadedFile($uploadDirectory, $uploadedFile, $submissionID);
        }

        // Get relative path to submission
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $dbPath = $dbPath . "/" . $submissionID . "." . $extension;

        // Push to database
        $stmt = $pdo->prepare('INSERT INTO `submissions` (`SubmissionID`, `SubmissionOwner`, `ContentType`, `ContentPath`, `ContentTitle`, `ContentDescription`) VALUES (:submissionid, :submissionOwner, :contentType, :contentPath, :contentTitle, :contentDescription)');
        $stmt->bindParam(':submissionid', $submissionID);
        $stmt->bindParam(':submissionOwner', $_SESSION['UserID']);
        $stmt->bindParam(':contentType', $type);
        $stmt->bindParam(':contentPath', $dbPath);
        $stmt->bindParam(':contentTitle', $title);
        $stmt->bindParam(':contentDescription', $description);
        $stmt->execute();

        $router = $this->router;
        return $response->withStatus(303)->withHeader('Location', $router->pathFor('submission', ['submissionid' => $submissionID]));
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

function moveUploadedFile($directory, UploadedFile $uploadedFile, $id)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%s', $id, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

$app->run();
