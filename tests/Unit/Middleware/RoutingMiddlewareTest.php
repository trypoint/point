<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Middleware\RoutingMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @small
 */
final class RoutingMiddlewareTest extends TestCase
{
    public function testAddsRouteMatchToRequest() : void
    {
        $app = AppFactory::create();
        $app->get('/test', static fn() => 'response');
        $container = $app->getContainer();
        $middleware = $container->get(RoutingMiddleware::class);
        $request = $this->createRequest('GET', '/test');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                $match = $request->getAttribute(\Point\Routing\RouteMatch::class);
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

                return $factory->createResponse(200)
                    ->withHeader('X-Match', $match?->isFound() ? 'found' : 'not-found');
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals('found', $response->getHeaderLine('X-Match'));
    }

    public function testRespectsBasePath() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $router->setBasePath('/api');
        $app->get('/test', static fn() => 'response');
        $container = $app->getContainer();
        $middleware = $container->get(RoutingMiddleware::class);
        $request = $this->createRequest('GET', '/api/test');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                $match = $request->getAttribute(\Point\Routing\RouteMatch::class);
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

                return $factory->createResponse(200)
                    ->withHeader('X-Match', $match?->isFound() ? 'found' : 'not-found');
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals('found', $response->getHeaderLine('X-Match'));
    }

    public function testThrowsNotFoundForUnknownRoute() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $middleware = $container->get(RoutingMiddleware::class);
        $request = $this->createRequest('GET', '/nonexistent');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                $match = $request->getAttribute(\Point\Routing\RouteMatch::class);
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

                return $factory->createResponse(200)
                    ->withHeader('X-Match', $match?->isNotFound() ? 'not-found' : 'other');
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals('not-found', $response->getHeaderLine('X-Match'));
    }

    private function createRequest(string $method, string $uri) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createServerRequest($method, $uri);
    }
}
