# Routing

## Overview

Point uses **FastRoute** for high-performance routing with support for:
- Static routes
- Dynamic parameters with constraints
- Route groups with shared prefixes
- Named routes for URL generation
- Route-specific middleware

## Basic Routes

```php
$app->get('/users', $handler);
$app->post('/users', $handler);
$app->put('/users/{id}', $handler);
$app->patch('/users/{id}', $handler);
$app->delete('/users/{id}', $handler);
$app->options('/users', $handler);
$app->any('/users', $handler); // Matches all HTTP methods
```

## Dynamic Parameters

```php
// Simple parameter
$app->get('/user/{id}', function ($request, $response, array $args) {
    // $args['id'] = matched value
    return $response;
});

// Multiple parameters
$app->get('/user/{userId}/post/{postId}', $handler);

// Optional parameter (with default)
$app->get('/users[/{page}]', function ($request, $response, array $args) {
    $page = $args['page'] ?? 1;
    return $response;
});

// Regex constraint
$app->get('/user/{id:\d+}', $handler);      // Only digits
$app->get('/post/{slug:[a-z0-9-]+}', $handler); // Alphanumeric + dash
```

## Route Groups

Group routes with a common prefix:

```php
$app->group('/api', function ($group) use ($handler, $authMiddleware) {
    $group->get('/users', $handler);
    $group->post('/users', $handler);
    
    // Nested groups
    $group->group('/v1', function ($group) use ($handler) {
        $group->get('/posts', $handler);
    });
    
    // Group middleware
    $group->add($authMiddleware);
    $group->get('/admin', $handler);
});
```

Group middleware executes **before** route middleware.

## Named Routes & URL Generation

```php
$route = $app->get('/user/{id}', $handler)->setName('user.profile');

// Generate URLs
$urlGenerator = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);

$url = $urlGenerator->urlFor('user.profile', ['id' => 123]);   // /user/123
$url = $urlGenerator->urlFor('user.profile', ['id' => 'a b']); // /user/a%20b
$url = $urlGenerator->urlFor('search', [], ['q' => 'test']);  // /search?q=test
```

Pass unencoded values to `urlFor()`. It percent-encodes each substituted value
and leaves the route pattern's delimiters unchanged. The router decodes the
request path once before matching. Percent-encoded slashes (`%2F` or `%2f`)
are unsupported and return HTTP 404. Define separate route segments for values
that span a slash. A literal value of `%2F` is encoded as `%252F` and decoded
once back to the literal text `%2F`.

Use unique route names when possible. Duplicate names resolve to the first
matching route in FastRoute's collector traversal. Renames take effect on the
next URL lookup.

### Route Methods

```php
$route = $app->get('/user/{id}', $handler);

$route->getName();        // 'user.profile' or null
$route->getMethods();     // ['GET']
$route->getPattern();     // '/user/{id}'
$route->getMiddleware();  // Array of route-specific middleware
$route->add($middleware); // Add middleware to this route only
```

## Route Matching

### RouteMatch Object

After routing, the request contains a `RouteMatch` attribute:

```php
use Point\Routing\RouteMatch;

$routeMatch = $request->getAttribute(RouteMatch::class);

if ($routeMatch->isFound()) {
    $route = $routeMatch->getRoute();
    $args = $routeMatch->getArguments();
} elseif ($routeMatch->isNotFound()) {
    // 404
} elseif ($routeMatch->isMethodNotAllowed()) {
    $allowed = $routeMatch->getAllowedMethods(); // ['GET', 'POST']
}
```

## Middleware on Routes

```php
// Single route
$app->get('/admin', $handler)->add($authMiddleware);

// Multiple middleware
$app->get('/admin', $handler)
    ->add($authMiddleware)
    ->add($rateLimitMiddleware);

// Group middleware (applies to all routes in group)
$app->group('/api', function ($group) use ($rateLimitMiddleware, $handler) {
    $group->add($authMiddleware);
    $group->get('/users', $handler);
    $group->post('/users', $handler);
});
```

## Execution Order with Default Middleware

```
1. HEAD and error handling
2. Global middleware (added via $app->add())
3. Routing and endpoint dispatch
4. Group middleware (parent to child)
5. Route middleware (specific to route)
6. Route handler
```

## Accessing Route Data in Middleware

```php
$routeMatch = $request->getAttribute(\Point\Routing\RouteMatch::class);

if ($routeMatch && $routeMatch->isFound()) {
    $route = $routeMatch->getRoute();
    $routeName = $route->getName();
    $routePattern = $route->getPattern();
    $arguments = $routeMatch->getArguments();
}
```

## Base Path

For applications in subdirectories:

```php
$app->setBasePath('/myapp');

// Routes match against /myapp/users, /myapp/api/v1/posts, etc.
```

The `BasePathMiddleware` handles this automatically when added.

## Custom Route Collector

Access the underlying FastRoute collector:

```php
$router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
$collector = $router->getRouteCollector();
// FastRoute\RouteCollector methods available
```

## HTTP Method Override

Enable form method overrides by parsing the form before running method override middleware:

```php
$app->add(new \Point\Middleware\FormBodyParserMiddleware());
$app->add(\Point\Middleware\MethodOverrideMiddleware::class);
```

A `POST /users` with `_method=DELETE` in its URL-encoded request body is routed as
`DELETE /users`. Clients can also use the `X-HTTP-Method-Override` request header.

---

## Related Documentation

- [Application](application.md)
- [Middleware](middleware.md)
- [Container](container.md)
