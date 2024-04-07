<?php
use Slim\Factory\AppFactory;
use Delight\Auth\Auth;
use DI\Container;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\UserAlreadyExistsException;
use Delight\Auth\TooManyRequestsException;

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

$app = AppFactory::create();

// CORS Middleware
$corsMiddleware = function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'https://movie-website')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
};
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

// Example route requiring authentication
$app->post('/signup', function ($request, $response, $args) use ($container) {
    $auth = $container->get('Auth');
    $data = $request->getParsedBody();
    $email = $data['email'];
    $password = $data['password'];
    $username = $data['username'];

    try {
        $userId = $auth->register($email, $password, $username, function ($selector, $token) {
            echo "Send verification email to user with selector $selector and token $token.";
        });

        // Success
        $response->getBody()->write(json_encode(['message' => "Successfully registered with ID $userId"]));
    } catch (InvalidEmailException $e) {
        $response->getBody()->write("Invalid email address $email");
    } catch (InvalidPasswordException $e) {
        $response->getBody()->write('Invalid password');
    } catch (UserAlreadyExistsException $e) {
        $response->getBody()->write('User already exists');
    } catch (TooManyRequestsException $e) {
        $response->getBody()->write('Too many requests');
    } catch (\Exception $e) {
        $response->getBody()->write('Error: ' . $e->getMessage());
    }

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();