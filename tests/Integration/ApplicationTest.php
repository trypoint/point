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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * @internal
 * @small
 */
final class ApplicationTest extends TestCase
{
    public function testApplicationHasContainer() : void
    {
        $app = $this->createApp();
        $container = $app->getContainer();
        self::assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testCreateApplication() : void
    {
        $app = $this->createApp();
        self::assertInstanceOf(App::class, $app);
    }

    public function testErrorMiddlewareHandlesExceptions() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/error', static fn() => throw new RuntimeException('Test error'));

        $request = $this->createRequest('GET', '/error', ['Accept' => 'application/json']);
        $response = $app->handle($request);

        self::assertEquals(500, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testGroupMiddleware() : void
    {
        $app = $this->createApp();
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

        $request = $this->createRequest('GET', '/api/test');
        $response = $app->handle($request);

        self::assertEquals('group-middleware', $response->getHeaderLine('X-Group'));
    }

    public function testHandleRequestReturnsResponse() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static function ($req, $res) {
            $res->getBody()->write('Hello');

            return $res;
        });

        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Hello', (string)$response->getBody());
    }

    public function testMiddlewareExecutionOrder() : void
    {
        $app = $this->createApp();
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

        $request = $this->createRequest('GET', '/');
        $app->handle($request);

        self::assertEquals(
            ['first-before', 'second-before', 'second-after', 'first-after'],
            $order
        );
    }

    public function testMiddlewareRegistration() : void
    {
        $app = $this->createApp();
        $middleware = static fn($req, $handler) => $handler->handle($req);
        $app->add($middleware);

        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $middlewareStack = $router->getMiddleware();
        self::assertCount(1, $middlewareStack);
    }

    public function testNestedRouteGroups() : void
    {
        $app = $this->createApp();
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

        $request = $this->createRequest('GET', '/api/v1/users');
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Users v1', (string)$response->getBody());
    }

    public function testRegisterDeleteRoute() : void
    {
        $app = $this->createApp();
        $app->delete('/', static fn($req, $res) => $res);

        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $data = $router->getRouteCollector()->getData();
        self::assertArrayHasKey('DELETE', $data[0]);
    }

    public function testRegisterGetRoute() : void
    {
        $app = $this->createApp();
        $app->get('/', static fn($req, $res) => $res);

        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $data = $router->getRouteCollector()->getData();
        self::assertArrayHasKey('GET', $data[0]);
        self::assertArrayHasKey('/', $data[0]['GET']);
    }

    public function testRegisterPatchRoute() : void
    {
        $app = $this->createApp();
        $app->patch('/', static fn($req, $res) => $res);

        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $data = $router->getRouteCollector()->getData();
        self::assertArrayHasKey('PATCH', $data[0]);
    }

    public function testRegisterPostRoute() : void
    {
        $app = $this->createApp();
        $app->post('/', static fn($req, $res) => $res);

        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $data = $router->getRouteCollector()->getData();
        self::assertArrayHasKey('POST', $data[0]);
    }

    public function testRegisterPutRoute() : void
    {
        $app = $this->createApp();
        $app->put('/', static fn($req, $res) => $res);

        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $data = $router->getRouteCollector()->getData();
        self::assertArrayHasKey('PUT', $data[0]);
    }

    public function testReturns404ForUnknownRoute() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('GET', '/unknown');
        $response = $app->handle($request);

        self::assertEquals(404, $response->getStatusCode());
    }

    public function testReturns404ForUnknownRouteWithHtmlAccept() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, false);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('GET', '/unknown', ['Accept' => 'text/html']);
        $response = $app->handle($request);

        self::assertEquals(404, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testReturns404ForFaviconRequest() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, false);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('GET', '/favicon.ico', ['Accept' => 'image/avif,image/webp,image/*,*/*;q=0.8']);
        $response = $app->handle($request);

        self::assertEquals(404, $response->getStatusCode());
    }

    public function testReturns405ForWrongMethodWithHtmlAccept() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, false);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('POST', '/', ['Accept' => 'text/html']);
        $response = $app->handle($request);

        self::assertEquals(405, $response->getStatusCode());
        self::assertStringContainsString('GET', $response->getHeaderLine('Allow'));
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testReturns500ForServerError() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, false);
        $app->addRoutingMiddleware();
        $app->get('/error', static fn() => throw new RuntimeException('Unexpected failure'));

        $request = $this->createRequest('GET', '/error', ['Accept' => 'text/html']);
        $response = $app->handle($request);

        self::assertEquals(500, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testReturns405ForWrongMethod() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('POST', '/');
        $response = $app->handle($request);

        self::assertEquals(405, $response->getStatusCode());
        self::assertStringContainsString('GET', $response->getHeaderLine('Allow'));
    }

    public function testRouteGroup() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->group('/api', static function ($group) : void {
            $group->get('/users', static function ($req, $res) {
                $res->getBody()->write('Users');

                return $res;
            });
        });

        $request = $this->createRequest('GET', '/api/users');
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Users', (string)$response->getBody());
    }

    public function testRouteMiddleware() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();

        $middleware = static function ($req, $handler) {
            $response = $handler->handle($req);

            return $response->withHeader('X-Custom', 'route-middleware');
        };

        $app->get('/test', static function ($req, $res) {
            return $res;
        })->add($middleware);

        $request = $this->createRequest('GET', '/test');
        $response = $app->handle($request);

        self::assertEquals('route-middleware', $response->getHeaderLine('X-Custom'));
    }

    public function testRouteWithDynamicParameters() : void
    {
        $app = $this->createApp();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/user/{id}', static function ($req, $res, $args) {
            $res->getBody()->write('User: ' . $args['id']);

            return $res;
        });

        $request = $this->createRequest('GET', '/user/123');
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('User: 123', (string)$response->getBody());
    }

    private function createApp() : App
    {
        return AppFactory::create();
    }

    private function createRequest(string $method, string $uri, array $headers = []) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $factory->createServerRequest($method, $uri);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
