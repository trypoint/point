# Container

## Overview

Point includes a **PSR-11 compatible** dependency injection container (`SimpleContainer`) with:
- Autowiring via reflection
- Service definitions as closures
- Support for external PSR-11 containers (PHP-DI, etc.)

## Default Container

```php
use Point\Factory\AppFactory;

$app = AppFactory::create();
$container = $app->getContainer(); // Point\Container\SimpleContainer
```

## Basic Usage

### Registering Services

```php
// Closure definition (factory)
$container->set(MyService::class, function ($container) {
    return new MyService($container->get(OtherService::class));
});

// Shared instance (singleton)
$container->set(Database::class, function ($container) {
    return new Database('mysql:host=localhost;dbname=test', 'user', 'pass');
});

// Value
$container->set('config.key', 'value');

// Class name (autowiring)
$container->set(MyService::class); // Creates via autowiring
```

### Retrieving Services

```php
$service = $container->get(MyService::class);

// Check existence
if ($container->has(MyService::class)) {
    $service = $container->get(MyService::class);
}
```

## Service Definitions

Point uses **definition arrays** for core services:

```php
use Point\Container\Definition\PointDefinitions;
use Point\Container\Definition\HttpDefinitions;
use Point\Container\Definition\NyholmDefinitions;

$definitions = [
    ...(new PointDefinitions())->getDefinitions(),
    ...(new HttpDefinitions())->getDefinitions(),
    ...(new NyholmDefinitions())->getDefinitions(),
];

$app = AppFactory::create($definitions);
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

All middleware are registered as container services:

```php
// Access middleware from container
$middleware = $container->get(\Point\Middleware\JsonExceptionMiddleware::class);
$configured = $middleware->withErrorDetails(true);
```

## Autowiring

The container automatically resolves constructor dependencies:

```php
class UserService
{
    public function __construct(
        private Database $database,
        private LoggerInterface $logger,
    ) {}
}

// No definition needed - autowired
$userService = $container->get(UserService::class);
```

### Constructor Parameters

- **Type-hinted services** → Resolved from container
- **Primitive types** → Must have default or be in definition
- **Arrays** → Must have default or be in definition

```php
class MyService
{
    public function __construct(
        private OtherService $service,     // Autowired
        private string $apiKey = 'default', // Default value
        private array $options = [],        // Default value
    ) {}
}
```

## External Containers (PHP-DI)

Point supports any PSR-11 container:

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
    MyService::class => \DI\autowire()->constructorParameter('apiKey', 'secret'),
]);

$app = AppFactory::createFromContainer($builder->build());
```

### PHP-DI Features

```php
$builder->addDefinitions([
    // Autowire with specific constructor params
    MyService::class => \DI\autowire()
        ->constructorParameter('apiKey', 'secret')
        ->property('cache', \DI\get(Cache::class)),
    
    // Factory method
    Database::class => \DI\factory([DatabaseFactory::class, 'create']),
    
    // Singleton (default in PHP-DI)
    Cache::class => \DI\object()->singleton(),
]);
```

## ContainerResolver

Resolves callables with container dependencies:

```php
use Point\Interfaces\ContainerResolverInterface;

$resolver = $container->get(ContainerResolverInterface::class);

// Resolve string class name
$callable = $resolver->resolve('MyController::method');

// Resolve closure with dependencies
$callable = $resolver->resolve(function (MyService $service) {
    return $service->doSomething();
});

// Resolve array callable
$callable = $resolver->resolve([MyController::class, 'method']);
```

The resolver:
1. Resolves class names from container
2. Injects dependencies into closures via `bindTo($container)`
3. Handles `Closure::bindTo` for non-static closures

## Custom Container Factory

```php
use Point\Interfaces\ContainerFactoryInterface;
use Point\Factory\AppFactory;

class MyContainerFactory implements ContainerFactoryInterface
{
    public function createContainer(array $definitions = []): \Psr\Container\ContainerInterface
    {
        // Build your custom container
        return $myContainer;
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

// Or create test container
$testContainer = new \Point\Container\SimpleContainer();
$testContainer->set(MyService::class, $mockService);
$app = AppFactory::createFromContainer($testContainer);
```

## Best Practices

1. **Use interfaces** for service contracts
2. **Register in definitions** for core services
3. **Use autowiring** for application services
4. **Keep definitions in separate files** for organization
5. **Avoid service locator pattern** — inject dependencies instead

```php
// Good: Dependency injection
class UserController
{
    public function __construct(private UserService $userService) {}
    
    public function show($request, $response, $args) { ... }
}

// Avoid: Service locator
class UserController
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
