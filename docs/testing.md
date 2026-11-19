# Testing

## Overview

Point uses **PHPUnit 13** for testing with:
- Unit tests for isolated components
- Integration tests for full request/response cycles
- PSR-7 implementation compatibility tests

## Running Tests

### All Tests

```bash
# With development dependencies
composer install --dev
composer test
```

### Specific Test Suites

```bash
# Unit tests only
vendor/bin/phpunit --configuration phpunit.xml --testsuite "Point Unit Tests"

# Integration tests only
vendor/bin/phpunit --configuration phpunit.xml --testsuite "Point Integration Tests"

# Single test class
vendor/bin/phpunit --configuration phpunit.xml --filter ContainerIntegrationTest

# Single test method
vendor/bin/phpunit --configuration phpunit.xml --filter testHandleRequestReturnsResponse
```

### Code Quality

```bash
# Static analysis
composer stan

# Code style check
composer cs:check

# Fix code style
composer cs:fix

# All checks
composer test:all
```

## Test Structure

```
tests/
├── bootstrap.php              # Test bootstrap
├── phpunit.xml                # PHPUnit configuration
├── Unit/                      # Unit tests
│   ├── Container/
│   │   ├── SimpleContainerTest.php
│   │   ├── DiContainerFactoryTest.php
│   │   ├── PointDefinitionsTest.php
│   │   └── HttpDefinitionsTest.php
│   ├── Middleware/
│   │   ├── MiddlewareTest.php
│   │   ├── ErrorExceptionMiddlewareTest.php
│   │   ├── ExceptionMiddlewareTest.php
│   │   ├── RoutingMiddlewareTest.php
│   │   ├── EndpointMiddlewareTest.php
│   │   ├── HeadMethodMiddlewareTest.php
│   │   ├── MethodOverrideMiddlewareTest.php
│   │   ├── OutputBufferingMiddlewareTest.php
│   │   └── ResponseFactoryMiddlewareTest.php
│   ├── Error/
│   │   └── HttpExceptionTest.php
│   ├── Http/
│   │   └── Psr7FactoryTest.php
│   └── Routing/
│       └── RouteTest.php
└── Integration/               # Integration tests
    ├── ApplicationTest.php
    ├── ContainerIntegrationTest.php
    ├── MiddlewareIntegrationTest.php
    ├── RoutingIntegrationTest.php
    └── Psr7ImplementationIntegrationTest.php
```

## Writing Unit Tests

### Basic Test Case

```php
<?php

declare(strict_types=1);

namespace Point\Tests\Unit\MyComponent;

use PHPUnit\Framework\TestCase;
use Point\MyComponent\MyClass;

/**
 * @internal
 * @small
 */
final class MyClassTest extends TestCase
{
    public function testDoesSomething() : void
    {
        $instance = new MyClass();
        
        $result = $instance->doSomething();
        
        self::assertEquals('expected', $result);
    }
}
```

### Testing with Container

```php
use Point\Factory\AppFactory;
use Point\Container\SimpleContainer;

$app = AppFactory::create();
$container = $app->getContainer();

// Get service
$service = $container->get(MyService::class);

// Override for testing
$container->set(MyService::class, $mockService);
```

### Testing Middleware

```php
use Point\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

$app = AppFactory::create();
$middleware = $app->getContainer()->get(MyMiddleware::class);

$factory = new Psr17Factory();
$request = $factory->createServerRequest('GET', '/');

$handler = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $factory->createResponse(200);
    }
};

$response = $middleware->process($request, $handler);

self::assertEquals(200, $response->getStatusCode());
```

## Writing Integration Tests

### Full Request Cycle

```php
use Point\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;

class MyIntegrationTest extends TestCase
{
    private App $app;
    private Psr17Factory $factory;
    
    protected function setUp(): void
    {
        $this->app = AppFactory::create();
        $this->app->addErrorMiddleware(true, true, true);
        $this->app->addRoutingMiddleware();
        
        $this->factory = new Psr17Factory();
        
        $this->app->get('/test', function ($request, $response) {
            $response->getBody()->write('OK');
            return $response;
        });
    }
    
    public function testRouteReturnsOk() : void
    {
        $request = $this->factory->createServerRequest('GET', '/test');
        $response = $this->app->handle($request);
        
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('OK', (string)$response->getBody());
    }
}
```

### Testing with Different PSR-7 Implementations

```php
use Point\Tests\Integration\Psr7ImplementationIntegrationTest;
// See Psr7ImplementationIntegrationTest.php for data provider pattern

/**
 * @dataProvider psr7Implementations
 */
public function testWithAllPsr7(
    string $name,
    ResponseFactoryInterface $responseFactory,
    ServerRequestFactoryInterface $requestFactory
): void {
    // Test runs 4 times (nyholm, laminas, guzzle, httpsoft)
}
```

## Test Helpers

### Bootstrap (tests/bootstrap.php)

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;

function createResponse(int $status = 200): \Psr\Http\Message\ResponseInterface
{
    $factory = new Psr17Factory();
    return $factory->createResponse($status);
}

function createApp(?\Psr\Container\ContainerInterface $container = null): \Point\App
{
    if ($container) {
        return \Point\Factory\AppFactory::createFromContainer($container);
    }
    return \Point\Factory\AppFactory::create();
}
```

### Common Assertions

```php
// Response assertions
self::assertEquals(200, $response->getStatusCode());
self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
self::assertEquals('expected', (string)$response->getBody());

// JSON response
$body = json_decode((string)$response->getBody(), true);
self::assertArrayHasKey('key', $body);
self::assertEquals('value', $body['key']);

// Exception assertions
$this->expectException(\Point\Exception\HttpNotFoundException::class);
$this->expectExceptionMessage('Not found');
```

## PSR-7 Implementation Tests

The `Psr7ImplementationIntegrationTest` runs all integration tests against:
- Nyholm (nyholm/psr7)
- Laminas (laminas/laminas-diactoros)
- Guzzle (guzzlehttp/psr7)
- HttpSoft (httpsoft/http-message + httpsoft/http-server-request)

This ensures framework compatibility with all major PSR-7 implementations.

## Continuous Integration

### GitHub Actions (`.github/workflows/ci.yml`)

```yaml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php-version: ['8.4', '8.5']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: mbstring, json, xml, simplexml
          coverage: none
      - run: composer install --no-progress --prefer-dist
      - run: composer validate --strict
      - run: composer dump-autoload -o --strict-psr
      - run: composer cs:check
      - run: composer stan
      - run: composer test
```

## Test Coverage

```bash
# Generate coverage report (requires xdebug or pcov)
vendor/bin/phpunit --configuration phpunit.xml --coverage-html coverage/

# Or with pcov (faster)
pcov enable
vendor/bin/phpunit --configuration phpunit.xml --coverage-html coverage/
```

## Best Practices

1. **Test behavior, not implementation** — Focus on public API
2. **Use data providers** — For testing multiple inputs
3. **Keep tests isolated** — No shared state between tests
4. **Name tests clearly** — `testMethodDoesSomethingWhenCondition`
5. **Use `@small`, `@medium`, `@large`** — For test suite organization
6. **Mock external dependencies** — Database, HTTP clients, etc.
7. **Test error cases** — 404, 405, 500, validation errors

## Debugging Tests

```bash
# Verbose output
vendor/bin/phpunit --configuration phpunit.xml --verbose

# Stop on first failure
vendor/bin/phpunit --configuration phpunit.xml --stop-on-failure

# Run specific test with debug
vendor/bin/phpunit --configuration phpunit.xml --filter testName --debug
```

---

## Related Documentation

- [Application](application.md)
- [Container](container.md)
- [Deployment](deployment.md)