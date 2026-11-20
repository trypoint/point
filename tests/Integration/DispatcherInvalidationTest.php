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
use ReflectionProperty;

/**
 * @internal
 */
final class DispatcherInvalidationTest extends TestCase
{
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
