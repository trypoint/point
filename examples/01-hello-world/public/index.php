<?php

/**
 * Point Framework - Hello World Example
 *
 * A minimal web application demonstrating basic routing and text responses.
 */

require __DIR__ . '/../vendor/autoload.php';

use Point\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

// Create application
$app = AppFactory::create();

// Configure PSR-17 factories
$psr17 = new Psr17Factory();
$container = $app->getContainer();
$container->set(\Psr\Http\Message\ResponseFactoryInterface::class, $psr17);
$container->set(\Psr\Http\Message\ServerRequestFactoryInterface::class, $psr17);

// Add error handling middleware (development mode)
$app->addErrorMiddleware(true, true, true);

// Add routing middleware
$app->addRoutingMiddleware();

// Root route
$app->get('/', function ($request, $response) {
    $response->getBody()->write('Hello, Point Framework!');
    return $response;
});

// Hello route with parameter
$app->get('/hello/{name}', function ($request, $response, array $args) {
    $name = htmlspecialchars($args['name'] ?? 'World');
    $response->getBody()->write("Hello, {$name}!");
    return $response;
});

// Run the application
$app->run();