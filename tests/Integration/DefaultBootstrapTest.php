<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Middleware\RoutingMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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

        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
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
