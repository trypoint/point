<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Point\Container\Definition\PointDefinitions;
use Point\Factory\AppFactory;
use Point\Interfaces\RouterInterface;
use Point\Middleware\ErrorExceptionMiddleware;
use Point\Middleware\ExceptionLoggingMiddleware;
use Point\Middleware\HtmlExceptionMiddleware;
use Point\Middleware\JsonExceptionMiddleware;
use Point\Middleware\RoutingMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * @internal
 * @small
 */
final class DefaultBootstrapTest extends TestCase
{
    private function createRequest(string $method = 'GET', string $uri = '/', array $headers = []) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $factory->createServerRequest($method, $uri);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    public function testHelloWorldWithoutManualMiddleware() : void
    {
        $app = AppFactory::create();
        $app->get('/', static function ($req, $res) {
            $res->getBody()->write('Hello, World!');

            return $res;
        });

        $request = $this->createRequest();
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Hello, World!', (string)$response->getBody());
    }

    public function testCustomMiddlewareExecutesBeforeRouting() : void
    {
        $app = AppFactory::create();
        $order = [];

        $app->add(static function ($req, $handler) use (&$order) : ResponseInterface {
            $order[] = 'custom-before';
            $response = $handler->handle($req);
            $order[] = 'custom-after';

            return $response;
        });

        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest();
        $app->handle($request);

        self::assertEquals(['custom-before', 'custom-after'], $order);
    }

    public function testConfiguredRequestHandlerTerminatesTheDefaultPipeline() : void
    {
        $accepted = createResponse(202);
        $accepted->getBody()->write('Accepted');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::exactly(6))->method('handle')->willReturn($accepted);
        $separateRouter = AppFactory::create();
        $separateRouter->add(static fn($request) => $handler->handle($request));
        $routerHandler = $separateRouter->getContainer()->get(RequestHandlerInterface::class);

        $responses = [];
        $heads = [];
        $manual = [];
        foreach ([$handler, $routerHandler] as $customHandler) {
            $app = AppFactory::create([RequestHandlerInterface::class => $customHandler]);
            $app->add(static fn($request, $next) => $next->handle($request)->withHeader('X-Middleware', 'ran'));
            $app->addRoutingMiddleware();
            $app->get('/', static fn($request, $response) => $response);

            $responses[] = $app->handle($this->createRequest());
            $heads[] = $app->handle($this->createRequest('HEAD'));
            $manual[] = $app->withDefaults(false)->handle($this->createRequest());
        }

        self::assertSame([202, 202], array_map(static fn($response) => $response->getStatusCode(), $responses));
        self::assertSame(['ran', 'ran'], array_map(static fn($response) => $response->getHeaderLine('X-Middleware'), $responses));
        self::assertSame(['Accepted', 'Accepted'], array_map(static fn($response) => (string)$response->getBody(), $responses));
        self::assertSame([202, 202], array_map(static fn($response) => $response->getStatusCode(), $heads));
        self::assertSame(['', ''], array_map(static fn($response) => (string)$response->getBody(), $heads));
        self::assertSame([$accepted, $accepted], $manual);
    }

    public function testBuiltInRequestHandlerKeepsPointRoutingInBothModes() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        self::assertSame($container->get(RouterInterface::class), $container->get(RequestHandlerInterface::class));

        $app->get('/routed', static fn($request, $response) => $response->withStatus(201));
        $request = $this->createRequest('GET', '/routed');
        self::assertSame(201, $app->handle($request)->getStatusCode());

        $app->withDefaults(false)->addRoutingMiddleware();
        self::assertSame(201, $app->handle($request)->getStatusCode());
    }

    public function testMiddlewareShortCircuits() : void
    {
        $app = AppFactory::create();

        $app->add(static function ($req, $handler) {
            $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $response = $factory->createResponse(403);
            $response->getBody()->write('Forbidden');

            return $response;
        });

        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest();
        $response = $app->handle($request);

        self::assertEquals(403, $response->getStatusCode());
        self::assertEquals('Forbidden', (string)$response->getBody());
    }

    public function testMultipleConsecutiveRequests() : void
    {
        $app = AppFactory::create();
        $count = 0;

        $app->add(static function ($req, $handler) use (&$count) : ResponseInterface {
            $count++;

            return $handler->handle($req);
        });

        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest();
        $response1 = $app->handle($request);
        $response2 = $app->handle($request);

        self::assertEquals(200, $response1->getStatusCode());
        self::assertEquals(200, $response2->getStatusCode());
        self::assertEquals(2, $count);
    }

    public function testReturns404ForUnknownRoute() : void
    {
        $app = AppFactory::create();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('GET', '/unknown');
        $response = $app->handle($request);

        self::assertEquals(404, $response->getStatusCode());
    }

    public function testReturns405ForWrongMethod() : void
    {
        $app = AppFactory::create();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('POST', '/');
        $response = $app->handle($request);

        self::assertEquals(405, $response->getStatusCode());
        self::assertStringContainsString('GET', $response->getHeaderLine('Allow'));
    }

    public function testReturns500ForServerError() : void
    {
        $app = AppFactory::create();
        $app->get('/error', static fn() => throw new RuntimeException('Unexpected failure'));

        $request = $this->createRequest('GET', '/error');
        $response = $app->handle($request);

        self::assertEquals(500, $response->getStatusCode());
    }

    public function testNoDuplicateDefaultMiddlewareOnExplicitRegistration() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();

        $router = $app->getContainer()->get(RouterInterface::class);
        $middleware = $router->getMiddleware();

        $routingCount = 0;
        $endpointCount = 0;

        foreach ($middleware as $entry) {
            if ($entry === RoutingMiddleware::class || $entry instanceof RoutingMiddleware) {
                $routingCount++;
            }
            if ($entry === \Point\Middleware\EndpointMiddleware::class || $entry instanceof \Point\Middleware\EndpointMiddleware) {
                $endpointCount++;
            }
        }

        self::assertSame(1, $routingCount, 'RoutingMiddleware should not be duplicated');
        self::assertSame(1, $endpointCount, 'EndpointMiddleware should not be duplicated');
    }

    public function testCustomErrorHandling() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/error', static fn() => throw new RuntimeException('Custom error'));

        $request = $this->createRequest('GET', '/error', ['Accept' => 'application/json']);
        $response = $app->handle($request);

        self::assertEquals(500, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        self::assertArrayHasKey('message', $body);
    }

    public static function errorMiddlewareFactories() : iterable
    {
        yield 'JSON renderer' => [JsonExceptionMiddleware::class];
        yield 'logger' => [ExceptionLoggingMiddleware::class];
    }

    #[DataProvider('errorMiddlewareFactories')]
    public function testFailedErrorRegistrationKeepsDefaultsAndAllowsRetry(string $service) : void
    {
        $failure = new RuntimeException('Error middleware configuration failed');
        $factory = (new PointDefinitions())->getDefinitions()[$service];
        $attempts = 0;
        $app = AppFactory::create([
            $service => static function (ContainerInterface $container) use ($factory, $failure, &$attempts) {
                if (++$attempts === 1) {
                    throw $failure;
                }

                return $factory($container);
            },
        ]);
        $router = $app->getContainer()->get(RouterInterface::class);

        try {
            $app->addErrorMiddleware();
            self::fail('Expected the original configuration exception');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }

        self::assertSame([], $router->getMiddleware());
        $request = $this->createRequest('GET', '/missing');
        self::assertSame(404, $app->handle($request)->getStatusCode());

        $app->addErrorMiddleware();
        self::assertSame([
            ErrorExceptionMiddleware::class,
            HtmlExceptionMiddleware::class,
            JsonExceptionMiddleware::class,
            ExceptionLoggingMiddleware::class,
        ], array_map(static fn($entry) => is_object($entry) ? $entry::class : $entry, $router->getMiddleware()));
        self::assertSame(404, $app->handle($request)->getStatusCode());
    }

    public function testExternalContainerDoesNotEnableDefaults() : void
    {
        $container = (new \Point\Container\DiContainerFactory())->createContainer();
        $app = AppFactory::createFromContainer($container);

        self::assertInstanceOf(\Point\App::class, $app);

        // Without routing middleware, handling should fail (no defaults auto-applied)
        $request = $this->createRequest();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The middleware pipeline is empty or exhausted.');
        $app->handle($request);
    }

    public function testOptOutOfAutomaticDefaults() : void
    {
        $app = AppFactory::create();
        $app->withDefaults(false);
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The middleware pipeline is empty or exhausted.');
        $app->handle($request);
    }

    public function testExistingBehaviorWithExplicitMiddleware() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static function ($req, $res) {
            $res->getBody()->write('Hello');

            return $res;
        });

        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Hello', (string)$response->getBody());
    }
}
