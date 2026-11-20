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
// Alternatively, register TimingMiddleware::class in the container and add it by ID.

// Route-specific middleware
$app->get('/admin', $handler)->add($authMiddleware);

// Group middleware
$app->group('/api', function ($group) use ($rateLimitMiddleware, $handler) {
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
// Configure the error stack explicitly, before routing.
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
// AppFactory::create() installs routing automatically.
// With defaults disabled, add routing after your other middleware:
$app->addRoutingMiddleware();
```

### HTTP Utilities

| Middleware | Purpose |
|------------|---------|
| `HeadMethodMiddleware` | Removes body for HEAD requests |
| `MethodOverrideMiddleware` | Allows `_method` / `X-Http-Method-Override` |
| `ContentLengthMiddleware` | Adds Content-Length for known body sizes, except on HTTP 204 |
| `OutputBufferingMiddleware` | Captures output buffering |
| `ResponseFactoryMiddleware` | Short-circuits the pipeline with an empty response |
| `BasePathMiddleware` | Handles subdirectory deployments |

```php
// Optional global middleware. HEAD suppression is already built into App.
$app->add(\Point\Middleware\MethodOverrideMiddleware::class);
$app->add(\Point\Middleware\ContentLengthMiddleware::class);
$app->add(\Point\Middleware\OutputBufferingMiddleware::class);
$app->add(\Point\Middleware\BasePathMiddleware::class);
```

### Body Parsers

```php
$app->add(new \Point\Middleware\JsonBodyParserMiddleware());
$app->add(new \Point\Middleware\XmlBodyParserMiddleware()); // Requires SimpleXML
$app->add(new \Point\Middleware\FormBodyParserMiddleware());
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

Attach this middleware to a route or group so it runs after route matching.

```php
use Point\Routing\RouteMatch;

class MyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        
        if ($routeMatch && $routeMatch->isFound()) {
            $route = $routeMatch->getRoute();
            $routeName = $route->getName() ?? $route->getPattern();
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
    public function __construct(
        private \Psr\Http\Message\ResponseFactoryInterface $responses,
        private \Closure $isAuthenticated,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! ($this->isAuthenticated)($request)) {
            $response = $this->responses->createResponse(401);
            $response->getBody()->write('{"error":"Unauthorized"}');
            return $response->withHeader('Content-Type', 'application/json');
        }
        
        return $handler->handle($request);
    }
}
```

## Error Handling in Middleware

```php
class SafeMiddleware implements MiddlewareInterface
{
    public function __construct(private \Psr\Http\Message\ResponseFactoryInterface $responses) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable) {
            return $this->responses->createResponse(500);
        }
    }
}
```

**Note**: For framework-level error handling, use `addErrorMiddleware()` instead.

## Testing Middleware

```php
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$middleware = new TimingMiddleware();

$request = (new ServerRequestFactory())->createServerRequest('GET', '/');
$handler = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return (new ResponseFactory())->createResponse(200);
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
