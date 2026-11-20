<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Integration;

use FastRoute\BadRouteException;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Interfaces\DispatcherInterface;
use Point\Interfaces\RouterInterface;
use Point\Routing\FastRouteDispatcher;
use Point\Routing\Route;
use Point\Routing\RouteGroup;
use ReflectionProperty;

/**
 * @internal
 */
final class DispatcherInvalidationTest extends TestCase
{
    public function testRetainedGroupsKeepPrefixesAfterWarmDispatch() : void
    {
        $app = AppFactory::create();
        $requests = new Psr17Factory();
        $order = [];
        $middleware = static function (string $label) use (&$order) : callable {
            return static function ($request, $handler) use ($label, &$order) {
                $order[] = $label;

                return $handler->handle($request);
            };
        };
        $handler = static fn($request, $response) => $response;
        $nested = null;
        $group = $app->group('/api', static function ($group) use ($handler, &$nested) {
            $group->get('/inside', $handler);
            $nested = $group->group('/v1', static function ($version) use ($handler) {
                $version->get('/inside', $handler);
            });
        });
        $group->add($middleware('api'));
        $nested->add($middleware('v1'));
        self::assertSame(200, $app->handle($requests->createServerRequest('GET', '/api/v1/inside'))->getStatusCode());
        self::assertSame(404, $app->handle($requests->createServerRequest('GET', '/api/later'))->getStatusCode());

        $group->get('/later', $handler)->setName('later');
        $nested->get('/items/{id}', $handler)->setName('item')->add($middleware('route'));
        $group->group('/v2', static function ($version) use ($handler) {
            $version->get('/inside', $handler);
        });

        $urls = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);
        self::assertSame('/api/later', $urls->urlFor('later'));
        self::assertSame(200, $app->handle($requests->createServerRequest('GET', $urls->urlFor('later')))->getStatusCode());
        self::assertSame('/api/v1/items/42', $urls->urlFor('item', ['id' => '42']));
        $order = [];
        self::assertSame(200, $app->handle($requests->createServerRequest('GET', $urls->urlFor('item', ['id' => '42'])))->getStatusCode());
        self::assertSame(['api', 'v1', 'route'], $order);
        self::assertSame(200, $app->handle($requests->createServerRequest('GET', '/api/v2/inside'))->getStatusCode());

        foreach (['/later', '/v2/inside', '/items/42', '/api/api/later'] as $path) {
            self::assertSame(404, $app->handle($requests->createServerRequest('GET', $path))->getStatusCode());
        }
    }

    public function testLateStaticDynamicAndNestedGroupRoutes() : void
    {
        $app = AppFactory::create();
        $requests = new Psr17Factory();
        $handler = static fn($request, $response) => $response;
        $app->get('/initial', $handler);

        self::assertSame(200, $app->handle($requests->createServerRequest('GET', '/initial'))->getStatusCode());
        self::assertSame(404, $app->handle($requests->createServerRequest('GET', '/late'))->getStatusCode());

        $app->get('/late', $handler);
        $app->group('/api', static function ($group) {
            $group->group('/v1', static function ($nested) {
                $nested->add(static function ($request, $handler) {
                    return $handler->handle($request)->withHeader('X-Group', 'v1');
                });
                $nested->get('/items/{id}', static function ($request, $response, array $arguments) {
                    $response->getBody()->write($arguments['id']);

                    return $response;
                });
            });
        });

        self::assertSame(200, $app->handle($requests->createServerRequest('GET', '/late'))->getStatusCode());
        $get = $app->handle($requests->createServerRequest('GET', '/api/v1/items/42'));
        self::assertSame(200, $get->getStatusCode());
        self::assertSame('42', (string)$get->getBody());
        self::assertSame('v1', $get->getHeaderLine('X-Group'));
        $head = $app->handle($requests->createServerRequest('HEAD', '/api/v1/items/42'));
        self::assertSame(200, $head->getStatusCode());
        self::assertSame('', (string)$head->getBody());
        $post = $app->handle($requests->createServerRequest('POST', '/api/v1/items/42'));
        self::assertSame(405, $post->getStatusCode());
        self::assertSame('GET, HEAD', $post->getHeaderLine('Allow'));
        self::assertSame(200, $app->handle($requests->createServerRequest('GET', '/initial'))->getStatusCode());
    }

    public function testPointGroupsAndDirectCollectorGroupsSharePrefixes() : void
    {
        $app = AppFactory::create();
        $requests = new Psr17Factory();
        $collector = $app->getContainer()->get(RouterInterface::class)->getRouteCollector();
        $handler = static fn($request, $response) => $response;

        $collector->addGroup('/outer', static function (RouteCollector $collector) use ($app, $handler) {
            $app->group('/api', static function ($group) use ($collector, $handler) {
                $group->get('/point', $handler)->setName('point');
                $collector->get('/raw', new Route(['GET'], '/outer/api/raw', $handler));
            });
        });

        $urls = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);
        self::assertSame('/outer/api/point', $urls->urlFor('point'));
        self::assertSame(200, $app->handle($requests->createServerRequest('GET', $urls->urlFor('point')))->getStatusCode());
        self::assertSame(200, $app->handle($requests->createServerRequest('GET', '/outer/api/raw'))->getStatusCode());
        self::assertSame(404, $app->handle($requests->createServerRequest('GET', '/outer/outer/api/raw'))->getStatusCode());
    }

    public function testRetainedGroupWorksWithAPlainFastRouteCollector() : void
    {
        $collector = new RouteCollector(new Std(), new GroupCountBased());
        $handler = static fn($request, $response) => $response;
        $group = new RouteGroup('/api', static function ($group) use ($handler) {
            $group->get('/inside', $handler);
        }, $collector);
        $collector->addGroup('/api', $group);
        $route = $group->get('/later', $handler);
        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollector')->willReturn($collector);
        $dispatcher = new FastRouteDispatcher($router);

        self::assertSame(DispatcherInterface::FOUND, $dispatcher->dispatch('GET', '/api/inside')[0]);
        self::assertSame([DispatcherInterface::FOUND, $route, []], $dispatcher->dispatch('GET', '/api/later'));
        self::assertSame([DispatcherInterface::NOT_FOUND], $dispatcher->dispatch('GET', '/later'));
    }

    public function testLateRegistrationThroughCollectorGroupsAliasesAndClone() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $dispatcher = $container->get(DispatcherInterface::class);
        $collector = $container->get(RouterInterface::class)->getRouteCollector();
        self::assertSame([DispatcherInterface::NOT_FOUND], $dispatcher->dispatch('GET', '/direct/item'));

        $clone = clone $collector;
        $clone->addGroup('/direct', static function (RouteCollector $groupedCollector) {
            $groupedCollector->get('/{id}', 'handler');
        });

        self::assertSame(
            [DispatcherInterface::FOUND, 'handler', ['id' => 'item']],
            $dispatcher->dispatch('GET', '/direct/item'),
        );
    }

    public function testDispatcherReusesSnapshotUntilSuccessfulRegistration() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $dispatcher = $container->get(DispatcherInterface::class);
        $collector = $container->get(RouterInterface::class)->getRouteCollector();
        $handler = static fn($request, $response) => $response;
        $route = $app->get('/existing', $handler);
        $snapshot = new ReflectionProperty(FastRouteDispatcher::class, 'dispatcher');
        self::assertSame(DispatcherInterface::FOUND, $dispatcher->dispatch('GET', '/existing')[0]);
        $initial = $snapshot->getValue($dispatcher);

        $route->setName('renamed');
        try {
            $collector->get('/existing', $route);
            self::fail('Expected FastRoute to reject the duplicate.');
        } catch (BadRouteException) {
            self::assertSame(DispatcherInterface::FOUND, $dispatcher->dispatch('GET', '/existing')[0]);
            self::assertSame($initial, $snapshot->getValue($dispatcher));
        }

        $collector->post('/existing', $route);
        self::assertSame(DispatcherInterface::FOUND, $dispatcher->dispatch('POST', '/existing')[0]);
        $updated = $snapshot->getValue($dispatcher);
        self::assertNotSame($initial, $updated);
        self::assertSame(DispatcherInterface::NOT_FOUND, $dispatcher->dispatch('GET', '/missing')[0]);
        self::assertSame($updated, $snapshot->getValue($dispatcher));
    }

    public function testPartialRegistrationInvalidatesDispatcherWhenLaterMethodFails() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $dispatcher = $container->get(DispatcherInterface::class);
        $collector = $container->get(RouterInterface::class)->getRouteCollector();
        $handler = static fn($request, $response) => $response;
        $app->get('/shared', $handler);
        self::assertSame([DispatcherInterface::METHOD_NOT_ALLOWED, ['GET']], $dispatcher->dispatch('PUT', '/shared'));

        $route = new Route(['PUT', 'GET'], '/shared', $handler);
        try {
            $collector->addRoute(['PUT', 'GET'], '/shared', $route);
            self::fail('Expected FastRoute to reject the duplicate GET route.');
        } catch (BadRouteException) {
            self::assertSame([DispatcherInterface::FOUND, $route, []], $dispatcher->dispatch('PUT', '/shared'));
        }
    }

    public function testCustomCollectorChangesAreDetectedWithoutRevisionSupport() : void
    {
        $collector = new RouteCollector(new Std(), new GroupCountBased());
        $collector->get('/first', 'first-handler');
        $replacement = new RouteCollector(new Std(), new GroupCountBased());
        $replacement->get('/second', 'second-handler');
        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollector')->willReturnCallback(static function () use (&$collector) : RouteCollector {
            return $collector;
        });
        $dispatcher = new FastRouteDispatcher($router);

        self::assertSame([DispatcherInterface::FOUND, 'first-handler', []], $dispatcher->dispatch('GET', '/first'));
        $collector->post('/first', 'post-handler');
        self::assertSame([DispatcherInterface::FOUND, 'post-handler', []], $dispatcher->dispatch('POST', '/first'));
        $collector = $replacement;
        self::assertSame([DispatcherInterface::NOT_FOUND], $dispatcher->dispatch('GET', '/first'));
        self::assertSame([DispatcherInterface::FOUND, 'second-handler', []], $dispatcher->dispatch('GET', '/second'));
    }
}
