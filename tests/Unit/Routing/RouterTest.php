<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Interfaces\RouteInterface;
use Point\Routing\RouteGroup;

/**
 * @internal
 * @small
 */
final class RouterTest extends TestCase
{
    public function testGetRouteCollector() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);

        $collector = $router->getRouteCollector();
        self::assertInstanceOf(\FastRoute\RouteCollector::class, $collector);
    }

    public function testGetSetBasePath() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);

        self::assertNull($router->getBasePath());

        $router->setBasePath('/api');
        self::assertSame('/api', $router->getBasePath());
    }

    public function testGroup() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);

        $group = $router->group('/api', static function ($group) : void {
            $group->get('/users', static fn() => 'users');
        });

        self::assertInstanceOf(RouteGroup::class, $group);
    }

    public function testMapRoute() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);

        $route = $router->map(['GET'], '/test', static fn() => 'response');

        self::assertInstanceOf(RouteInterface::class, $route);
        self::assertSame(['GET'], $route->getMethods());
        self::assertSame('/test', $route->getPattern());
    }
}
