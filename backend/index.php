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

// Example route requiring authentication
$app->get('/signup', function ($request, $response, $args) use ($container) {
    echo "here";
    $auth = $container->get('Auth');
    $data = $request->getParsedBody();
    $email = $data['email'];
    $password = $data['password'];

    try {
        $userId = $auth->register($email, $password, null, function ($selector, $token) {
            echo "Send verification email to user with selector $selector and token $token.";
        });

        // Success
        $response->getBody()->write("Successfully registered with ID $userId");
    } catch (InvalidEmailException $e) {
        $response->getBody()->write('Invalid email address');
    } catch (InvalidPasswordException $e) {
        $response->getBody()->write('Invalid password');
    } catch (UserAlreadyExistsException $e) {
        $response->getBody()->write('User already exists');
    } catch (TooManyRequestsException $e) {
        $response->getBody()->write('Too many requests');
    } catch (\Exception $e) {
        $response->getBody()->write('Error: ' . $e->getMessage());
    }

    return $response;
});

$app->run();