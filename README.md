# Point Framework

**Point is a lightweight, modular PHP microframework built for flexibility and extensibility.**

[![PHP Version](https://img.shields.io/badge/PHP-8.4%20%7C%208.5-8892BF?logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![Build Status](https://github.com/trypoint/point/actions/workflows/ci.yml/badge.svg)](https://github.com/trypoint/point/actions/workflows/ci.yml)

---

## Features

- **PSR-7 / PSR-15 / PSR-17** compliant HTTP message handling
- **PSR-11** compatible dependency injection container (built-in, with PHP-DI support)
- **FastRoute** based routing with dynamic parameters and route groups
- **Middleware pipeline** with FIFO execution order
- **Content negotiation** for JSON, HTML, XML, and plain text error responses
- **Error handling** with detailed stack traces in development
- **Default PSR-7 implementation** via httpsoft (no additional packages required)
- **Optional PSR-7 implementations** via nyholm/psr7, laminas/laminas-diactoros, guzzlehttp/psr7
- **PHP 8.4 / 8.5** native with strict typing

---

## Requirements

- **PHP** >= 8.4.27
- **Composer** 2.x
- **Optional XML middleware** requires SimpleXML for request parsing or DOM for error rendering.

---

## Installation

```bash
composer require trypoint/point
```

Point includes **httpsoft/http-message** and **httpsoft/http-server-request** as the default PSR-7/PSR-17 implementation. No additional packages are required.

Optional: replace with an alternative PSR-7 implementation:
```bash
# Nyholm (fast, lightweight)
composer require nyholm/psr7 nyholm/psr7-server

# Laminas
composer require laminas/laminas-diactoros

# Guzzle
composer require guzzlehttp/psr7
```

---

## Quick Start

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Point\Factory\AppFactory;

// Create application
$app = AppFactory::create();

// GET route
$app->get('/', function ($request, $response) {
    $response->getBody()->write('Hello, World!');
    return $response;
});

// Dynamic route with parameters
$app->get('/user/{id}', function ($request, $response, array $args) {
    $response->getBody()->write('User: ' . $args['id']);
    return $response;
});

// JSON response
$app->get('/api/users', function ($request, $response) {
    $data = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Run the application
$app->run();
```

Save as `public/index.php` and serve with PHP's built-in server:

```bash
php -S localhost:8080 -t public
```

Visit `http://localhost:8080/` to see the response.

---

## Routing

### Basic Routes

```php
$app->get('/users', $handler);
$app->post('/users', $handler);
$app->put('/users/{id}', $handler);
$app->delete('/users/{id}', $handler);
$app->patch('/users/{id}', $handler);
$app->options('/users', $handler);
$app->any('/users', $handler); // Matches any HTTP method
```

### Route Parameters

```php
$app->get('/users/{id}', function ($request, $response, array $args) {
    // $args['id'] contains the matched value
    return $response;
});

// Optional parameters
$app->get('/users[/{id}]', $handler);

// Constraints via regex
$app->get('/users/{id:\d+}', $handler); // Only digits
```

### Route Groups

```php
$app->group('/api', function ($group) use ($handler) {
    $group->get('/users', $handler);
    $group->post('/users', $handler);
    
    $group->group('/v1', function ($group) use ($handler) {
        $group->get('/posts', $handler);
    });
});
```

### Route Middleware

```php
$app->get('/admin', $handler)->add($authMiddleware);

$app->group('/api', function ($group) use ($rateLimitMiddleware, $handler) {
    $group->add($rateLimitMiddleware);
    $group->get('/users', $handler);
});
```

### Named Routes & URL Generation

```php
$route = $app->get('/user/{id}', $handler)->setName('user.profile');

$urlGenerator = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);
$url = $urlGenerator->urlFor('user.profile', ['id' => 123]); // /user/123
```

Pass raw parameter values. Point percent-encodes each substituted value.
Encoded slashes (`%2F` and `%2f`) in request paths return HTTP 404.
Use separate route segments for values that span a slash.

---

## Middleware

Middleware follows **PSR-15** (`MiddlewareInterface`) and executes in **FIFO order** (first added = outermost).

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExampleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Before: modify request, add attributes, etc.
        $request = $request->withAttribute('startTime', microtime(true));
        
        $response = $handler->handle($request);
        
        // After: modify response, add headers, logging, etc.
        $duration = microtime(true) - $request->getAttribute('startTime');
        return $response->withHeader('X-Response-Time', (string)$duration);
    }
}

// Add to application
$app->add(new ExampleMiddleware());
// Alternatively, register ExampleMiddleware::class in the container and add it by ID.
```

### Built-in Middleware

| Middleware | Purpose |
|------------|---------|
| `RoutingMiddleware` | Resolves routes, adds `RouteMatch` to request |
| `EndpointMiddleware` | Executes matched route handler |
| `ErrorExceptionMiddleware` | Converts PHP errors to `ErrorException` |
| `HtmlExceptionMiddleware` | Renders HTML error pages |
| `JsonExceptionMiddleware` | Renders JSON error responses |
| `XmlExceptionMiddleware` | Renders XML error responses |
| `PlainTextExceptionMiddleware` | Renders plain text error responses |
| `ExceptionLoggingMiddleware` | Logs exceptions via PSR-3 logger |
| `HeadMethodMiddleware` | Removes body for HEAD requests |
| `MethodOverrideMiddleware` | Allows `_method` parameter override |
| `ContentLengthMiddleware` | Adds Content-Length for known body sizes, except on HTTP 204 |
| `OutputBufferingMiddleware` | Captures output buffering |
| `ResponseFactoryMiddleware` | Short-circuits the pipeline with an empty response |
| `BasePathMiddleware` | Handles subdirectory deployments |

### Error Handling

```php
// Development: detailed errors
$app->addErrorMiddleware(true, true, true);

// Production: minimal errors
$app->addErrorMiddleware(false, true, false, $logger);

// Custom error handler
$app->addErrorMiddleware(
    displayErrorDetails: false,
    logErrors: true,
    logErrorDetails: true,
    logger: $customPsr3Logger
);
```

---

## Dependency Injection

Point includes a **PSR-11 compatible container** (`SimpleContainer`) with autowiring.

```php
$app = AppFactory::create();
$container = $app->getContainer();

// Register a service
$container->set(MyService::class, function ($c) {
    return new MyService($c->get(OtherService::class));
});

// Get a service
$service = $container->get(MyService::class);
```

### Using PHP-DI (Optional)

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

---

## PSR Interoperability

Point works with **any PSR-7/PSR-17 implementation**. By default, **httpsoft** is used:

```php
// Default (included): httpsoft
$responseFactory = new \HttpSoft\Message\ResponseFactory();
$requestFactory = new \HttpSoft\Message\ServerRequestFactory();
```

Alternative implementations (install separately):

```php
// Nyholm (fast, lightweight)
$psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();

// Laminas
$responseFactory = new \Laminas\Diactoros\ResponseFactory();
$requestFactory = new \Laminas\Diactoros\ServerRequestFactory();

// Guzzle
$factory = new \GuzzleHttp\Psr7\HttpFactory();
```

Configure in container:

```php
$app->getContainer()->set(\Psr\Http\Message\ResponseFactoryInterface::class, $responseFactory);
$app->getContainer()->set(\Psr\Http\Message\ServerRequestFactoryInterface::class, $requestFactory);
```

All framework code uses PSR interfaces — no coupling to a specific implementation.

---

## Testing

```bash
# Run all tests
composer test

# Run with code style check
composer test:all
```

Requires development dependencies:
```bash
composer install
```

---

## Deployment

### Apache (.htaccess)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### PHP Built-in Server (Development)

```bash
php -S localhost:8080 -t public
```

---

## Security

Report vulnerabilities to [**security.point@madalinmariusstan.dev**](mailto:security.point@madalinmariusstan.dev).

See [SECURITY.md](SECURITY.md) for supported versions and disclosure policy.

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Ensure tests pass: `composer test:all`
4. Submit a pull request

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

---

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.

Copyright (c) 2011-2024 Josh Lockhart (original Slim Framework)
Copyright (c) 2024 Madalin Marius Stan (Point Framework)
