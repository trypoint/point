# Container

## Overview

Point includes a **PSR-11 compatible** dependency injection container (`SimpleContainer`) for explicit instances, values, and factory closures. Point also accepts external PSR-11 containers such as PHP-DI.

## Default Container

```php
use Point\Factory\AppFactory;

$app = AppFactory::create();
$container = $app->getContainer(); // Point\Container\SimpleContainer
```

## Basic Usage

### Registering Services

```php
// Instance and factory
$container->set(DateTimeZone::class, new DateTimeZone('UTC'));
$container->set(DateTimeImmutable::class, static function ($container): DateTimeImmutable {
    return new DateTimeImmutable('now', $container->get(DateTimeZone::class));
});

// Value
$container->set('config.key', 'value');
```

### Retrieving Services

```php
$startedAt = $container->get(DateTimeImmutable::class);
$sameInstance = $startedAt === $container->get(DateTimeImmutable::class);

$registered = $container->has(DateTimeImmutable::class);
$missing = $container->has(stdClass::class); // false
```

Factory results are shared, including a `null` result. Calling `set()` again for an ID replaces its definition and clears the cached result. Class names are service IDs and require explicit registration. Calling `get(stdClass::class)` in the example throws `Psr\Container\NotFoundExceptionInterface`. If a registered factory fails because a dependency is missing, the container reports `Psr\Container\ContainerExceptionInterface` and retains the original exception as its cause.

## Service Definitions

The default bootstrap registers Point's core services and the first complete HTTP provider. Pass a definition array to override specific service IDs.

```php
use HttpSoft\Message\ResponseFactory;
use Point\Factory\AppFactory;
use Psr\Http\Message\ResponseFactoryInterface;

$app = AppFactory::create([
    ResponseFactoryInterface::class => new ResponseFactory(),
]);
```

### Core Definitions

| Service | Implementation |
|---------|----------------|
| `App::class` | `Point\App` |
| `RouterInterface::class` | `Point\Routing\Router` |
| `DispatcherInterface::class` | `Point\Routing\FastRouteDispatcher` |
| `EmitterInterface::class` | `Point\Emitter\ResponseEmitter` |
| `ContainerResolverInterface::class` | `Point\Container\ContainerResolver` |
| `RequestHandlerInvocationStrategyInterface::class` | `Point\Strategy\RequestResponse` |
| `UrlGeneratorInterface::class` | `Point\Routing\UrlGenerator` |
| `LoggerInterface::class` | `Psr\Log\NullLogger` |
| `ResponseFactoryInterface::class` | From PSR-17 implementation |
| `ServerRequestFactoryInterface::class` | From PSR-17 implementation |

Application routes belong to the collector returned by `RouterInterface::getRouteCollector()`. The
`FastRoute\RouteCollector::class` service can resolve to a separate instance. Use the router's collector
when accessing the application's routes.

With automatic defaults enabled, a custom `RequestHandlerInterface` service replaces Point's routing
and endpoint middleware as the final step. Application middleware, error handling, and HEAD response
handling still wrap the custom handler. With `withDefaults(false)`, the configured handler owns the
whole request pipeline, and Point still suppresses HEAD response bodies.

Point restores group scope when a callback throws from a nested `RouteGroup::group()` backed by a
plain FastRoute collector. Routes registered before the exception remain available. Direct calls to a
custom collector's `addGroup()` follow that collector's own exception behavior.

### Middleware Definitions

Point registers its default pipeline middleware as container services.

```php
// Access middleware from container
$middleware = $container->get(\Point\Middleware\JsonExceptionMiddleware::class);
$configured = $middleware->withErrorDetails(true);
```

## External Containers (PHP-DI)

Install `php-di/php-di` in your application if you want constructor autowiring. PHP-DI can resolve class-typed constructor parameters and accept explicit values for parameters such as an API key.

```php
use DI\ContainerBuilder;
use Point\Factory\AppFactory;
use Point\Container\Definition\PointDefinitions;
use Point\Container\Definition\HttpDefinitions;

$builder = new ContainerBuilder();
$builder->addDefinitions((new PointDefinitions())->getDefinitions());
$builder->addDefinitions((new HttpDefinitions())->getDefinitions());

// Add your own definitions
$builder->addDefinitions([
    MyService::class => \DI\autowire()->constructorParameter('apiKey', 'example-only'),
]);

$app = AppFactory::createFromContainer($builder->build())->withDefaults();
```

`createFromContainer()` uses the supplied container and leaves automatic middleware defaults disabled until `withDefaults()` is called.

### PHP-DI Features

```php
$builder->addDefinitions([
    // Autowire with specific constructor params
    MyService::class => \DI\autowire()
        ->constructorParameter('apiKey', 'example-only')
        ->property('cache', \DI\get(Cache::class)),
    
    // Factory method
    Database::class => \DI\factory([DatabaseFactory::class, 'create']),
    
    // PHP-DI shares resolved entries by default
    Cache::class => \DI\autowire(),
]);
```

## ContainerResolver

Use `resolve()` to retrieve a service or resolve a method reference. Use `resolveCallable()` when the result must be callable.

```php
use Point\Interfaces\ContainerResolverInterface;

$resolver = $container->get(ContainerResolverInterface::class);

// Retrieve a registered service by ID
$controller = $resolver->resolve(MyController::class);

// Resolve a method on the registered service
$callable = $resolver->resolveCallable(MyController::class . ':method');

// Resolve a non-static closure with access to the container as $this
$callable = $resolver->resolveCallable(function () {
    return $this->get(DateTimeImmutable::class);
});

// Resolve an array whose first element is a registered service ID
$callable = $resolver->resolve([MyController::class, 'method']);
```

PHP callables such as public static method references are accepted as callables. In the closure example, `resolveCallable()` binds the non-static closure to the container. The closure looks up its own dependencies and the caller supplies any arguments.

## Custom Container Factory

```php
use Point\Container\DiContainerFactory;
use Point\Interfaces\ContainerFactoryInterface;
use Point\Factory\AppFactory;

class MyContainerFactory implements ContainerFactoryInterface
{
    public function createContainer(array $definitions = []): \Psr\Container\ContainerInterface
    {
        return (new DiContainerFactory())->createContainer(array_replace(
            ['app.environment' => 'development'],
            $definitions,
        ));
    }
}

AppFactory::setContainerFactory(new MyContainerFactory());
$app = AppFactory::create();
```

## Service Decoration

Extend or wrap existing services:

```php
$container->set(\Point\Middleware\JsonExceptionMiddleware::class, function ($c) {
    $middleware = new \Point\Middleware\JsonExceptionMiddleware(
        $c->get(\Psr\Http\Message\ResponseFactoryInterface::class)
    );
    return $middleware->withErrorDetails(true); // Customize
});
```

## Testing with Container

```php
use Point\Factory\AppFactory;

$app = AppFactory::create();
$container = $app->getContainer();

// Override for testing
$container->set(Database::class, $mockDatabase);
$container->set(LoggerInterface::class, $mockLogger);

// Or override a service when bootstrapping a test app
$app = AppFactory::create([MyService::class => $mockService]);
```

## Best Practices

1. **Use interfaces** for service contracts
2. **Register in definitions** for core services
3. **Register application services explicitly** in SimpleContainer or use optional PHP-DI autowiring
4. **Keep definitions in separate files** for organization
5. **Avoid service locator pattern** — inject dependencies instead

```php
// Good: Dependency injection
class UserController
{
    public function __construct(private UserService $userService) {}
    
    public function show($request, $response, $args) { /* Use $this->userService. */ }
}

// Avoid: Service locator
class ServiceLocatorController
{
    public function show($request, $response, $args) {
        $service = $this->container->get(UserService::class); // Don't do this
    }
}
```

---

## Related Documentation

- [Application](application.md)
- [Middleware](middleware.md)
- [Request & Response](request-response.md)
