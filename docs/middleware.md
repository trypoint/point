# Middleware

## Overview

Middleware in Point follows **PSR-15** (`MiddlewareInterface`) and executes in **FIFO order** (first added = outermost, wraps the next middleware).

## Creating Middleware

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TimingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);
        
        // Before: can modify request
        $request = $request->withAttribute('startTime', $start);
        
        $response = $handler->handle($request);
        
        // After: can modify response
        $duration = microtime(true) - $start;
        return $response->withHeader('X-Response-Time', (string)$duration);
    }
}
```

## Adding Middleware

```php
// Global middleware (every request)
$app->add(new TimingMiddleware());
$app->add(TimingMiddleware::class); // Resolved from container

// Route-specific middleware
$app->get('/admin', $handler)->add($authMiddleware);

// Group middleware
$app->group('/api', function ($group) {
    $group->add($rateLimitMiddleware);
    $group->get('/users', $handler);
});
```

## Execution Order

```
Request → Middleware 1 (outermost)
            → Middleware 2
                → Middleware 3 (innermost)
                    → Route Handler
                ← Middleware 3
            ← Middleware 2
        ← Middleware 1
Response
```

**First added = outermost = executes first on request, last on response.**

## Built-in Middleware

### Error Handling

| Middleware | Purpose |
|------------|---------|
| `ErrorExceptionMiddleware` | Converts PHP errors to `ErrorException` |
| `HtmlExceptionMiddleware` | Renders HTML error pages |
| `JsonExceptionMiddleware` | Renders JSON error responses |
| `XmlExceptionMiddleware` | Renders XML error responses |
| `PlainTextExceptionMiddleware` | Renders plain text errors |
| `ExceptionLoggingMiddleware` | Logs exceptions via PSR-3 |

```php
// Add all error middleware at once
$app->addErrorMiddleware(
    displayErrorDetails: true,  // Show details in response
    logErrors: true,            // Log to logger
    logErrorDetails: true,      // Include stack traces in logs
    logger: $customLogger       // Optional custom PSR-3 logger
);
```

### Routing

| Middleware | Purpose |
|------------|---------|
| `RoutingMiddleware` | Resolves route, adds `RouteMatch` to request |
| `EndpointMiddleware` | Executes matched route handler |

```php
// Required for routing to work
$app->addRoutingMiddleware();
// Internally adds: RoutingMiddleware + EndpointMiddleware
```

### HTTP Utilities

| Middleware | Purpose |
|------------|---------|
| `HeadMethodMiddleware` | Removes body for HEAD requests |
| `MethodOverrideMiddleware` | Allows `_method` / `X-Http-Method-Override` |
| `ContentLengthMiddleware` | Adds Content-Length header |
| `OutputBufferingMiddleware` | Captures output buffering |
| `ResponseFactoryMiddleware` | Creates empty response for handler |
| `BasePathMiddleware` | Handles subdirectory deployments |

```php
$app->add(\Point\Middleware\HeadMethodMiddleware::class);
$app->add(\Point\Middleware\MethodOverrideMiddleware::class);
$app->add(\Point\Middleware\ContentLengthMiddleware::class);
$app->add(\Point\Middleware\OutputBufferingMiddleware::class);
$app->add(\Point\Middleware\ResponseFactoryMiddleware::class);
$app->add(\Point\Middleware\BasePathMiddleware::class);
```

### Body Parsers

```php
$app->add(\Point\Middleware\JsonBodyParserMiddleware::class);
$app->add(\Point\Middleware\XmlBodyParserMiddleware::class);
$app->add(\Point\Middleware\FormUrlEncodedBodyParserMiddleware::class);
```

Parse request body into `$request->getParsedBody()`.

## Middleware from Container

Any service registered in the container can be used as middleware:

```php
$container->set(MyMiddleware::class, function ($c) {
    return new MyMiddleware($c->get(OtherService::class));
});

$app->add(MyMiddleware::class); // Resolved with dependencies
```

## Accessing Route Information

```php
use Point\Routing\RouteMatch;

class MyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        
        if ($routeMatch && $routeMatch->isFound()) {
            $route = $routeMatch->getRoute();
            $routeName = $route->getName();
            $arguments = $routeMatch->getArguments();
            
            // Add route info to response headers
            $response = $handler->handle($request);
            return $response->withHeader('X-Route', $routeName);
        }
        
        return $handler->handle($request);
    }
}
```

## Short-Circuiting

Return a response early without calling `$handler->handle()`:

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->isAuthenticated($request)) {
            $response = $handler->handle($request); // Or create custom response
            return $response->withStatus(401)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(json_encode(['error' => 'Unauthorized']));
        }
        
        return $handler->handle($request);
    }
}
```

## Error Handling in Middleware

```php
class SafeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            // Log error
            // Return custom error response
            $response = $handler->handle($request); // Won't work, handler already threw
            // Instead, create new response:
            $factory = $request->getAttribute(\Psr\Http\Message\ResponseFactoryInterface::class);
            // Or get from container
            return $response->withStatus(500);
        }
    }
}
```

**Note**: For framework-level error handling, use `addErrorMiddleware()` instead.

## Testing Middleware

```php
use Point\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$app = AppFactory::create();
$middleware = $app->getContainer()->get(MyMiddleware::class);

$request = $factory->createServerRequest('GET', '/');
$handler = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $factory->createResponse(200);
    }
};

$response = $middleware->process($request, $handler);
// Assert on response
```

---

## Related Documentation

- [Application](application.md)
- [Routing](routing.md)
- [Error Handling](error-handling.md)