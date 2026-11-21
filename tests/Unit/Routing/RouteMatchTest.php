<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Point\Routing\Route;
use Point\Routing\RouteMatch;

/**
 * @internal
 * @small
 */
final class RouteMatchTest extends TestCase
{
    public function testFound() : void
    {
        $route = new Route(['GET'], '/test', static fn() => 'response');
        $match = RouteMatch::found($route, ['id' => '123']);

        self::assertTrue($match->isFound());
        self::assertFalse($match->isNotFound());
        self::assertFalse($match->isMethodNotAllowed());
        self::assertSame($route, $match->getRoute());
        self::assertSame(['id' => '123'], $match->getArguments());
    }

    public function testMethodNotAllowed() : void
    {
        $match = RouteMatch::methodNotAllowed(['GET', 'POST']);

        self::assertFalse($match->isFound());
        self::assertFalse($match->isNotFound());
        self::assertTrue($match->isMethodNotAllowed());
        self::assertSame(['GET', 'POST'], $match->getAllowedMethods());
    }

    public function testNotFound() : void
    {
        $match = RouteMatch::notFound();

        self::assertFalse($match->isFound());
        self::assertTrue($match->isNotFound());
        self::assertFalse($match->isMethodNotAllowed());
        self::assertNull($match->getRoute());
        self::assertSame([], $match->getArguments());
    }
}
