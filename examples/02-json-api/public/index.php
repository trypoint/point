<?php

/**
 * Point Framework - JSON API Example
 *
 * A minimal JSON API demonstrating GET/POST requests and HTTP status codes.
 */

require __DIR__ . '/../vendor/autoload.php';

use Point\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Point\Exception\HttpNotFoundException;
use Point\Exception\HttpBadRequestException;
use Point\Exception\HttpMethodNotAllowedException;

// Create application
$app = AppFactory::create();

// Configure PSR-17 factories
$psr17 = new Psr17Factory();
$container = $app->getContainer();
$container->set(\Psr\Http\Message\ResponseFactoryInterface::class, $psr17);
$container->set(\Psr\Http\Message\ServerRequestFactoryInterface::class, $psr17);

// Add error handling middleware (development mode)
$app->addErrorMiddleware(true, true, true);

// Add JSON body parser for POST requests
$app->add(new \Point\Middleware\JsonBodyParserMiddleware());

// In-memory "database"
$users = [
    1 => ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
    2 => ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
];
$nextId = 3;

// Helper: JSON response
$jsonResponse = function ($response, $data, int $status = 200) use ($psr17) {
    return $response
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json')
        ->withBody($psr17->createStream(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)));
};

// GET /api/users - List all users
$app->get('/api/users', function ($request, $response) use ($users, $jsonResponse) {
    return $jsonResponse($response, ['users' => array_values($users)]);
});

// GET /api/users/{id} - Get single user
$app->get('/api/users/{id}', function ($request, $response, array $args) use ($users, $jsonResponse) {
    $id = (int) $args['id'];
    
    if (! isset($users[$id])) {
        throw new HttpNotFoundException($request);
    }
    
    return $jsonResponse($response, ['user' => $users[$id]]);
});

// POST /api/users - Create new user
$app->post('/api/users', function ($request, $response) use ($users, $jsonResponse, &$nextId) {
    $data = $request->getParsedBody();
    
    if (! is_array($data)) {
        throw new HttpBadRequestException($request, 'Invalid JSON body');
    }
    
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    
    if ($name === '') {
        throw new HttpBadRequestException($request, 'Name is required');
    }
    
    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new HttpBadRequestException($request, 'Valid email is required');
    }
    
    // Check duplicate email
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            throw new HttpBadRequestException($request, 'Email already exists');
        }
    }
    
    $user = [
        'id' => $nextId,
        'name' => $name,
        'email' => $email,
    ];
    
    $users[$nextId] = $user;
    $nextId++;
    
    return $jsonResponse($response, ['user' => $user], 201);
});

// DELETE /api/users/{id} - Delete user
$app->delete('/api/users/{id}', function ($request, $response, array $args) use ($users, $jsonResponse) {
    $id = (int) $args['id'];
    
    if (! isset($users[$id])) {
        throw new HttpNotFoundException($request);
    }
    
    unset($users[$id]);
    
    return $jsonResponse($response, ['message' => 'User deleted']);
});

// Run the application
$app->run();