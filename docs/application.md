# Application

## Creating an Application

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Point\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

$app = AppFactory::create();

// Configure PSR-17 factories (optional, but recommended for consistency)
$psr17 = new Psr17Factory();
$container = $app->getContainer();
$container->set(\Psr\Http\Message\ResponseFactoryInterface::class, $psr17);
$container->set(\Psr\Http\Message\ServerRequestFactoryInterface::class, $psr17);
```

## Adding Middleware

```php
// Global middleware (executed for every request)
$app->add(new MyMiddleware());
$app->add(MyMiddleware::class); // Resolved from container

// Error handling middleware
$app->addErrorMiddleware(
    displayErrorDetails: true,   // Show stack traces
    logErrors: true,             // Log to PSR-3 logger
    logErrorDetails: true        // Include details in logs
);

// Routing middleware (required for route matching)
$app->addRoutingMiddleware();
```

## Defining Routes

```php
// Simple routes
$app->get('/', $handler);
$app->post('/users', $handler);

// Dynamic parameters
$app->get('/user/{id}', $handler);
$app->get('/user/{id:\d+}', $handler); // Regex constraint

// Route groups
$app->group('/api', function ($group) {
    $group->get('/users', $handler);
    $group->post('/users', $handler);
});

// Named routes for URL generation
$app->get('/user/{id}', $handler)->setName('user.profile');
```

## Running the Application

### Handle a Single Request (Testing)

```php
use Nyholm\Psr7\Factory\Psr17Factory;

$factory = new Psr17Factory();
$request = $factory->createServerRequest('GET', '/');
$response = $app->handle($request);
```

### Run (Production)

```php
// Creates request from PHP globals, emits response
$app->run();

// Or with a custom request
$app->run($customRequest);
```

## Application Lifecycle

1. **Request Creation** — From globals or provided `ServerRequestInterface`
2. **Middleware Pipeline** — Executes in FIFO order
   - Error middleware (outermost)
   - Application middleware
   - Routing middleware
   - Endpoint middleware (innermost)
3. **Route Matching** — `RoutingMiddleware` resolves route
4. **Handler Execution** — `EndpointMiddleware` invokes route handler
5. **Response Emission** — `EmitterInterface` sends response

## Configuration

### Base Path

For subdirectory deployments:

```php
$app->setBasePath('/myapp');
// Routes now match under /myapp/*
```

### Custom Container

```php
use DI\ContainerBuilder;
use Point\Factory\AppFactory;
use Point\Container\Definition\PointDefinitions;
use Point\Container\Definition\HttpDefinitions;

$builder = new ContainerBuilder();
$builder->addDefinitions((new PointDefinitions())->getDefinitions());
$builder->addDefinitions((new HttpDefinitions())->getDefinitions());

$app = AppFactory::createFromContainer($builder->build());
```

### Custom PSR-17 Factories

```php
$app->getContainer()->set(
    \Psr\Http\Message\ResponseFactoryInterface::class,
    $myResponseFactory
);
$app->getContainer()->set(
    \Psr\Http\Message\ServerRequestFactoryInterface::class,
    $myRequestFactory
);
```

## Quick Start Example

```php
<?php
// public/index.php

require __DIR__ . '/../vendor/autoload.php';

use Point\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

$app = AppFactory::create();

$psr17 = new Psr17Factory();
$app->getContainer()->set(\Psr\Http\Message\ResponseFactoryInterface::class, $psr17);
$app->getContainer()->set(\Psr\Http\Message\ServerRequestFactoryInterface::class, $psr17);

$app->addErrorMiddleware(true, true, true);
$app->addRoutingMiddleware();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Hello, Point!');
    return $response;
});

$app->get('/hello/{name}', function ($request, $response, array $args) {
    $response->getBody()->write('Hello, ' . htmlspecialchars($args['name']) . '!');
    return $response;
});

$app->run();
```

Serve:

```bash
php -S localhost:8080 -t public
```

Visit:
- `http://localhost:8080/` → "Hello, Point!"
- `http://localhost:8080/hello/World` → "Hello, World!"