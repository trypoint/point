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
$app->group('/api', function ($group) {
    $group->get('/users', $handler);
    $group->post('/users', $handler);
    
    // Nested groups
    $group->group('/v1', function ($group) {
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

$url = $urlGenerator->generate('user.profile', ['id' => 123]);     // /user/123
$url = $urlGenerator->generate('user.profile', ['id' => 123], []); // /user/123
$url = $urlGenerator->generate('search', [], ['q' => 'test']);     // /search?q=test
```

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
$app->group('/api', function ($group) {
    $group->add($authMiddleware);
    $group->get('/users', $handler);
    $group->post('/users', $handler);
});
```

## Execution Order

```
1. Global middleware (added via $app->add())
2. Error middleware (outermost)
3. Group middleware (parent to child)
4. Route middleware (specific to route)
5. EndpointMiddleware (executes handler)
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

Enable `_method` parameter override for forms:

```php
$app->add(\Point\Middleware\MethodOverrideMiddleware::class);
```

Now `POST /users?_method=DELETE` is treated as `DELETE /users`.

---

## Related Documentation

- [Application](application.md)
- [Middleware](middleware.md)
- [Container](container.md)