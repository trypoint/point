<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Point\App;
use Point\Factory\AppFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * @internal
 * @medium
 */
final class Psr7ImplementationIntegrationTest extends TestCase
{
    /**
     * @return array<string, array{string, ResponseFactoryInterface, ServerRequestFactoryInterface}>
     */
    public static function psr7Implementations() : array
    {
        return [
            'nyholm' => [
                'nyholm',
                new \Nyholm\Psr7\Factory\Psr17Factory(),
                new \Nyholm\Psr7\Factory\Psr17Factory(),
            ],
            'laminas' => [
                'laminas',
                new \Laminas\Diactoros\ResponseFactory(),
                new \Laminas\Diactoros\ServerRequestFactory(),
            ],
            'guzzle' => [
                'guzzle',
                new \GuzzleHttp\Psr7\HttpFactory(),
                new \GuzzleHttp\Psr7\HttpFactory(),
            ],
            'httpsoft' => [
                'httpsoft',
                new \HttpSoft\Message\ResponseFactory(),
                new \HttpSoft\Message\ServerRequestFactory(),
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testAppFactoryCreatesContainer(string $name, ResponseFactoryInterface $responseFactory, ServerRequestFactoryInterface $requestFactory) : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        self::assertInstanceOf(ContainerInterface::class, $container);
        self::assertTrue($container->has(App::class));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testErrorMiddlewareHandlesExceptions(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/error', static fn() => throw new RuntimeException('Test error'));

        $request = $this->createRequest($requestFactory, 'GET', '/error', ['Accept' => 'application/json']);
        $response = $app->handle($request);

        self::assertEquals(500, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testGroupMiddleware(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();

        $middleware = static function ($req, $handler) {
            $response = $handler->handle($req);

            return $response->withHeader('X-Group', 'group-middleware');
        };

        $app->group('/api', static function ($group) use ($middleware) : void {
            $group->add($middleware);
            $group->get('/test', static function ($req, $res) {
                return $res;
            });
        });

        $request = $this->createRequest($requestFactory, 'GET', '/api/test');
        $response = $app->handle($request);

        self::assertEquals('group-middleware', $response->getHeaderLine('X-Group'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testHandleRequestReturnsResponse(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static function ($req, $res) {
            $res->getBody()->write('Hello');

            return $res;
        });

        $request = $this->createRequest($requestFactory, 'GET', '/');
        $response = $app->handle($request);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Hello', (string)$response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testMiddlewareExecutionOrder(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $order = [];

        $app->add(static function ($req, $handler) use (&$order) {
            $order[] = 'first-before';
            $response = $handler->handle($req);
            $order[] = 'first-after';

            return $response;
        });

        $app->add(static function ($req, $handler) use (&$order) {
            $order[] = 'second-before';
            $response = $handler->handle($req);
            $order[] = 'second-after';

            return $response;
        });

        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest($requestFactory, 'GET', '/');
        $app->handle($request);

        self::assertEquals(
            ['first-before', 'second-before', 'second-after', 'first-after'],
            $order
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testNestedRouteGroups(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->group('/api', static function ($group) : void {
            $group->group('/v1', static function ($group) : void {
                $group->get('/users', static function ($req, $res) {
                    $res->getBody()->write('Users v1');

                    return $res;
                });
            });
        });

        $request = $this->createRequest($requestFactory, 'GET', '/api/v1/users');
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Users v1', (string)$response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testReturns404ForUnknownRoute(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest($requestFactory, 'GET', '/unknown');
        $response = $app->handle($request);

        self::assertEquals(404, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testReturns405ForWrongMethod(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest($requestFactory, 'POST', '/');
        $response = $app->handle($request);

        self::assertEquals(405, $response->getStatusCode());
        self::assertStringContainsString('GET', $response->getHeaderLine('Allow'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testRouteMiddleware(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();

        $middleware = static function ($req, $handler) {
            $response = $handler->handle($req);

            return $response->withHeader('X-Custom', 'route-middleware');
        };

        $app->get('/test', static function ($req, $res) {
            return $res;
        })->add($middleware);

        $request = $this->createRequest($requestFactory, 'GET', '/test');
        $response = $app->handle($request);

        self::assertEquals('route-middleware', $response->getHeaderLine('X-Custom'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('psr7Implementations')]
    public function testRouteWithDynamicParameters(
        string $name,
        ResponseFactoryInterface $responseFactory,
        ServerRequestFactoryInterface $requestFactory
    ) : void {
        $app = $this->createApp($responseFactory);
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/user/{id}', static function ($req, $res, $args) {
            $res->getBody()->write('User: ' . $args['id']);

            return $res;
        });

        $request = $this->createRequest($requestFactory, 'GET', '/user/123');
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('User: 123', (string)$response->getBody());
    }

    private function createApp(ResponseFactoryInterface $responseFactory) : App
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $container->set(ResponseFactoryInterface::class, $responseFactory);

        return $app;
    }

    private function createRequest(
        ServerRequestFactoryInterface $requestFactory,
        string $method,
        string $uri,
        array $headers = []
    ) : ServerRequestInterface {
        $request = $requestFactory->createServerRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
