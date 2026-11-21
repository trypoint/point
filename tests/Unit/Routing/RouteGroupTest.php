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
use Psr\Http\Server\MiddlewareInterface;

/**
 * @internal
 * @small
 */
final class RouteGroupTest extends TestCase
{
    public function testCreateRouteGroup() : void
    {
        $group = AppFactory::create()->group('/api', static function () : void {});
        self::assertSame('/api', $group->getPrefix());
    }

    public function testNestedRouteGroups() : void
    {
        $parent = AppFactory::create()->group('/api', static function () : void {});
        $child = $parent->group('/v1', static function () : void {});

        self::assertSame($parent, $child->getRouteGroup());
    }

    public function testRouteGroupWithMiddleware() : void
    {
        $middleware = $this->createStub(MiddlewareInterface::class);
        $group = AppFactory::create()->group('/api', static function () : void {})->addMiddleware($middleware);
        self::assertCount(1, $group->getMiddleware());
    }
}
