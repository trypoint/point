# Migrating from Slim to Point

Point requires PHP 8.4.27 or later. Install it with `composer require trypoint/point`. The bundled HttpSoft implementation is enough to run an application. `AppFactory::create()` enables default routing and error handling immediately. Slim 4 apps commonly register those middleware explicitly. The Slim 5 development branch also uses explicit registration and its current default container factory expects an external DI implementation. Follow Point's examples below when moving an application from either line.

## Routes, groups, and URLs

Save this as `public/index.php`. Run `php -S localhost:8080 -t public` from the project directory.

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Point\Factory\AppFactory;
use Point\Interfaces\UrlGeneratorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app = AppFactory::create();

$app->get('/', static function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $response->getBody()->write('Hello, World!');

    return $response;
});

$api = $app->group('/api', static function ($group): void {
    $group->get('/items/{id}', static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments,
    ): ResponseInterface {
        $response->getBody()->write(json_encode(['id' => $arguments['id']], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    })->setName('item');
});

$api->get('/health', static fn($request, $response) => $response->withStatus(204));

$urls = $app->getContainer()->get(UrlGeneratorInterface::class);
// $urls->urlFor('item', ['id' => 'a b']) returns /api/items/a%20b.

$app->run();
```

Use `post()`, `put()`, `patch()`, and `delete()` the same way as `get()`. Use `map()` to register one handler under several HTTP methods. Nest groups with `$group->group(...)`. Routes added through a retained group keep its prefix.

Point supplies route parameters in the handler's third argument. Route and group middleware can read them through `$request->getAttribute(\Point\Routing\RouteMatch::class)?->getArguments()`. Global middleware normally enters before routing, so route information is available only after a route has been matched. The JSON response above uses only PSR-7 operations.

Use `$app->setBasePath('/shop')` if the server mounts the app beneath `/shop`. Routes keep their original patterns and dispatch happens under `/shop`. Both `urlFor()` and `relativeUrlFor()` include the configured base path in Point. Slim 4's `relativeUrlFor()` leaves that prefix out. Pass raw values to Point's URL generator. It percent-encodes parameters, and the dispatcher returns 404 for a percent-encoded slash in a path.

## Middleware and error handling

Slim 4 runs the last middleware added first. Point runs the first added first within each layer. If a Slim app adds logging last to make it wrap authentication, add logging first on Point. Point executes outer group middleware before inner groups and route middleware.

With `AppFactory::create()`, global middleware runs inside the default error handling and before routing. When assembling a stack manually, register the error handler first, then global middleware, and call `addRoutingMiddleware()` last. Keep error details disabled in production. Pass a PSR-3 logger to `addErrorMiddleware()` if the host application should receive exception logs. Point returns the app from that helper. Customize behavior with PSR-15 middleware instead of chaining Slim's error-renderer registration methods.

## PSR-11 containers and HTTP factories

`AppFactory::create($definitions)` accepts service overrides keyed by class or interface. For an existing PSR-11 container, register Point's definitions and selected HTTP definitions through the container's own configuration mechanism. `createFromContainer()` expects those services and an explicit middleware stack. This standalone example uses Point's small built-in PSR-11 container to show the wiring:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Point\Container\Definition\HttpSoftDefinitions;
use Point\Container\Definition\PointDefinitions;
use Point\Container\SimpleContainer;
use Point\Factory\AppFactory;

$container = new SimpleContainer(array_replace(
    (new PointDefinitions())->getDefinitions(),
    (new HttpSoftDefinitions())->getDefinitions(),
));

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(false, false);
$app->addRoutingMiddleware();
$app->get('/', static function ($request, $response) {
    $response->getBody()->write('Hello, World!');

    return $response;
});
$app->run();
```

Point automatically chooses the first complete provider in this order: Nyholm, Laminas, Guzzle, then bundled HttpSoft. Nyholm needs both `nyholm/psr7` and `nyholm/psr7-server` for automatic selection. Use `AppFactory::create($definitions)` to override PSR-17 interfaces and Point's `ServerRequestCreatorInterface` when choosing explicitly. The globals creator is separate from PSR-17's server-request factory. Supply an existing PSR-7 request to `$app->handle($request)` in tests or hosts that build requests themselves.

See the [middleware guide](middleware.md), [routing guide](routing.md), and [container guide](container.md) for the related APIs.
