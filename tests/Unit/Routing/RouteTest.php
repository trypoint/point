<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Point\Interfaces\RouteInterface;
use Point\Routing\Route;

/**
 * @internal
 * @small
 */
final class RouteTest extends TestCase
{
    public function testCreateRoute() : void
    {
        $route = new Route(['GET'], '/test', static fn() => 'response');
        self::assertInstanceOf(RouteInterface::class, $route);
        self::assertSame(['GET'], $route->getMethods());
        self::assertSame('/test', $route->getPattern());
    }

    public function testRouteWithMiddleware() : void
    {
        $middleware = new class () implements \Psr\Http\Server\MiddlewareInterface {
            public function process(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler) : \Psr\Http\Message\ResponseInterface
            {
                return $handler->handle($request);
            }
        };
        $route = (new Route(['GET'], '/test', static fn() => 'response'))->addMiddleware($middleware);
        self::assertCount(1, $route->getMiddleware());
    }

    public function testRouteWithName() : void
    {
        $route = (new Route(['GET'], '/test', static fn() => 'response'))->setName('test.name');
        self::assertSame('test.name', $route->getName());
    }
}
