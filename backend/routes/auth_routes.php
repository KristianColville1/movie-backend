<?php

namespace Kristian\Routes;

use Slim\App;
use DI\Container;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\UserAlreadyExistsException;
use Delight\Auth\AttemptCancelledException;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\TooManyRequestsException;
use Kristian\Apps\Mail\Mailer;

return function (App $app, Container $container) {
    // Signs user up
    $app->post('/signup', function ($request, $response, $args) use ($container) {
        $auth = $container->get('Auth');
        $mailer = $container->get(Mailer::class);
        $data = $request->getParsedBody();
        $email = $data['email'];
        $password = $data['password'];
        $username = $data['username'];

        try {
            $userId = $auth->register($email, $password, $username, function ($selector, $token) use ($email, $mailer, $username) {
                // Generate the verification link
                $verificationUrl = "http://movie-backend.local/verify_email?selector=" . urlencode($selector) . "&token=" . urlencode($token);

                // Prepare email content
                $subject = "Verify Your Email";
                $body = "<p>Please click the following link to verify your email: <a href='" . $verificationUrl . "'>" . $verificationUrl . "</a></p>";
                $altBody = "Please visit the following link to verify your email: " . $verificationUrl;

                // Send verification email
                if (!$mailer->send($email, $username, $subject, $body, $altBody)) {
                    throw new \Exception("Failed to send verification email.");
                }
            });

            // Success
            $response->getBody()->write(json_encode(['message' => "Successfully registered with ID $userId"]));
        } catch (InvalidEmailException $e) {
            $response->getBody()->write(json_encode(['error' => 'Invalid email address']));
        } catch (InvalidPasswordException $e) {
            $response->getBody()->write(json_encode(['error' => 'Invalid password']));
        } catch (UserAlreadyExistsException $e) {
            $response->getBody()->write(json_encode(['error' => 'User already exists']));
        } catch (TooManyRequestsException $e) {
            $response->getBody()->write(json_encode(['error' => 'Too many requests']));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        }

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200); // is json and okay
    });

    // Signs user in
    $app->post('/signin', function ($request, $response, $args) use ($container) {
        $auth = $container->get('Auth');
        $data = $request->getParsedBody();

        $username_or_email = $data['username_or_email'] ?? '';
        $password = $data['password'] ?? '';

        $rememberDuration = 60 * 60 * 24 * 365.25; // 1 year in seconds

        try {
            // login method automatically handles both email and username.
            $auth->login($username_or_email, $password, $rememberDuration = 0);

            // If login is successful, gather the user data
            $userData = [
                'userId' => $auth->getUserId(),
                'email' => $auth->getEmail(),
                'username' => $auth->getUsername(), // Assuming you have a method to get the username
                // Add any other user-related information here
            ];

            // Write the success message and user data to the response body
            $responseBody = json_encode([
                'message' => "Successfully signed in",
                'user' => $userData
            ]);

            // Send the JSON response with a 200 OK status
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (InvalidEmailException $e) {
            error_log("Tried as email");
            try {
                $auth->loginWithUsername($username_or_email, $password, $rememberDuration = 0);
                // If login is successful, gather the user data
                $userData = [
                    'userId' => $auth->getUserId(),
                    'email' => $auth->getEmail(),
                    'username' => $auth->getUsername(), // Assuming you have a method to get the username
                    // Add any other user-related information here
                ];

                // Write the success message and user data to the response body
                $responseBody = json_encode([
                    'message' => "Successfully signed in",
                    'user' => $userData
                ]);

                // Send the JSON response with a 200 OK status
                $response->getBody()->write($responseBody);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } catch (\Delight\Auth\UnknownUsernameException $e) {
                $response->getBody()->write(json_encode(['error' => 'Username not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            } catch (InvalidPasswordException $e) {
                // This catches password errors on the second attempt
                $response->getBody()->write(json_encode(['error' => 'Wrong password']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
            // Handle other exceptions as needed.
        } catch (InvalidPasswordException $e) {
            $response->getBody()->write(json_encode(['error' => 'Wrong password']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        } catch (EmailNotVerifiedException $e) {
            $response->getBody()->write(json_encode(['error' => 'Email not verified']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        } catch (AttemptCancelledException $e) {
            $response->getBody()->write(json_encode(['error' => 'Attempt cancelled']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        } catch (TooManyRequestsException $e) {
            $response->getBody()->write(json_encode(['error' => 'Too many requests']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'An error occurred: ' . $e->getMessage()]));
            
        }

        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

    });

    // verifies user
    $app->get('/verify_email', function ($request, $response, $args) use ($container) {
        $auth = $container->get('Auth');
        $queryParams = $request->getQueryParams();
        $selector = $queryParams['selector'] ?? '';
        $token = $queryParams['token'] ?? '';

        try {
            // Verify the email using the selector and token
            $auth->confirmEmail($selector, $token);

            // Success
            return $response->withRedirect('/', 200);
        } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
            $message = ['error' => 'Invalid token'];
        } catch (\Delight\Auth\TokenExpiredException $e) {
            $message = ['error' => 'Token expired'];
        } catch (UserAlreadyExistsException $e) {
            $message = ['error' => 'Email already verified'];
        } catch (TooManyRequestsException $e) {
            $message = ['error' => 'Too many requests'];
        } catch (\Exception $e) {
            $message = ['error' => 'An error occurred: ' . $e->getMessage()];
        }

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    });
};