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

/**
 * @internal
 * @small
 */
final class FastRouteDispatcherTest extends TestCase
{


    public function testDispatchFound() : void
    {
        $app = AppFactory::create();
        $app->get('/test', static fn() => 'response');
        $dispatcher = $app->getContainer()->get(\Point\Interfaces\DispatcherInterface::class);

        $result = $dispatcher->dispatch('GET', '/test');

        self::assertSame(\Point\Interfaces\DispatcherInterface::FOUND, $result[0]);
        self::assertInstanceOf(RouteInterface::class, $result[1]);
    }

    public function testDispatchMethodNotAllowed() : void
    {
        $app = AppFactory::create();
        $app->get('/test', static fn() => 'response');
        $dispatcher = $app->getContainer()->get(\Point\Interfaces\DispatcherInterface::class);

        $result = $dispatcher->dispatch('POST', '/test');

        self::assertSame(\Point\Interfaces\DispatcherInterface::METHOD_NOT_ALLOWED, $result[0]);
        self::assertSame(['GET'], $result[1]);
    }

    public function testDispatchNotFound() : void
    {
        $app = AppFactory::create();
        $app->get('/test', static fn() => 'response');
        $dispatcher = $app->getContainer()->get(\Point\Interfaces\DispatcherInterface::class);

        $result = $dispatcher->dispatch('GET', '/nonexistent');

        self::assertSame(\Point\Interfaces\DispatcherInterface::NOT_FOUND, $result[0]);
    }

    public function testDispatchWithDynamicParameters() : void
    {
        $app = AppFactory::create();
        $app->get('/user/{id}', static fn($req, $res, $args) => $res->getBody()->write($args['id']));
        $dispatcher = $app->getContainer()->get(\Point\Interfaces\DispatcherInterface::class);

        $result = $dispatcher->dispatch('GET', '/user/123');

        self::assertSame(\Point\Interfaces\DispatcherInterface::FOUND, $result[0]);
        self::assertSame(['id' => '123'], $result[2]);
    }
}
