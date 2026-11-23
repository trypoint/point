<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Routing;

use FastRoute\DataGenerator\GroupCountBased as RouteDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Routing\RouteGroup;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;

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

    public function testPlainCollectorRestoresNestedScopeAfterCallbackFailure() : void
    {
        $collector = new RouteCollector(new Std(), new RouteDataGenerator());
        $failure = new RuntimeException('Nested registration failed');
        $handler = static fn($request, $response) => $response;
        $group = new RouteGroup('/outer', static function (RouteGroup $outer) use ($failure, $handler) : void {
            try {
                $outer->group('/inner', static function (RouteGroup $inner) use ($failure, $handler) : void {
                    $inner->get('/before-failure', $handler);

                    throw $failure;
                });
                self::fail('Expected the original callback exception');
            } catch (RuntimeException $exception) {
                self::assertSame($failure, $exception);
            }

            $outer->get('/after-failure', $handler);
            $outer->group('/sibling', static fn(RouteGroup $sibling) => $sibling->get('/ok', $handler));
        }, $collector);
        $collector->addGroup('/outer', $group);
        $collector->addRoute('GET', '/root', $handler);

        $dispatcher = new Dispatcher($collector->getData());
        foreach (['/outer/inner/before-failure', '/outer/after-failure', '/outer/sibling/ok', '/root'] as $path) {
            self::assertSame(Dispatcher::FOUND, $dispatcher->dispatch('GET', $path)[0], $path);
        }
        self::assertSame(Dispatcher::NOT_FOUND, $dispatcher->dispatch('GET', '/outer/inner/after-failure')[0]);
    }
}
