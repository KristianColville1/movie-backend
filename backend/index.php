<?php
use Slim\Factory\AppFactory;
use Delight\Auth\Auth;
use DI\Container;
use Kristian\Apps\Mail\Mailer;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/env.php';

// Create PHP-DI Container
$container = new Container();
AppFactory::setContainer($container);

// Set up database connection for Auth
$container->set('PDO', function () {
    return new PDO('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST . ':' . DB_PORT . ';charset=' . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
});

// Initialize and add Auth to the container
$container->set('Auth', function ($container) {
    return new Auth($container->get('PDO'));
});

// Adds mailer to the container to use throughout
$container->set(Mailer::class, function(){
    return new Mailer();
});

$app = AppFactory::create();

// CORS Middleware
$corsMiddleware = function ($request, $handler) {
    $response = $handler->handle($request);
    $allowedOrigins = ['http://movie-website.local', 'http://localhost', 'http://localhost:3000'];
    $origin = $request->getHeaderLine('Origin');

    if (in_array($origin, $allowedOrigins)) {
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
    }

    return $response
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
};
$app->add($corsMiddleware);
$app->add($corsMiddleware);

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// JSON Parsing Middleware
$jsonParsingMiddleware = function ($request, $handler) {
    $contentType = $request->getHeaderLine('Content-Type');
    if (strstr($contentType, 'application/json')) {
        $contents = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $request = $request->withParsedBody($contents);
        }
    }
    return $handler->handle($request);
};

// Add JSON Parsing Middleware to the application
$app->add($jsonParsingMiddleware);

// Routes
(require __DIR__ . '/routes/auth_routes.php')($app, $container); // auth routes - sign up, login etc


$app->run();