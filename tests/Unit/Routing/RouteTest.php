<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Routing;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Interfaces\RouteInterface;
use Point\Routing\FastRouteDispatcher;
use Point\Routing\Route;
use Point\Routing\RouteGroup;
use Point\Routing\RouteMatch;
use Point\Routing\Router;

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

/**
 * @internal
 * @small
 */
final class RouteGroupTest extends TestCase
{
    public function testCreateRouteGroup() : void
    {
        $group = new RouteGroup('/api');
        self::assertSame('/api', $group->getPattern());
    }

    public function testNestedRouteGroups() : void
    {
        $parent = new RouteGroup('/api');
        $child = new RouteGroup('/v1');
        $child->setRouteGroup($parent);

        self::assertSame($parent, $child->getRouteGroup());
    }

    public function testRouteGroupWithMiddleware() : void
    {
        $middleware = static fn($req, $handler) => $handler->handle($req);
        $group = (new RouteGroup('/api'))->addMiddleware($middleware);
        self::assertCount(1, $group->getMiddleware());
    }
}

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

/**
 * @internal
 * @small
 */
final class FastRouteDispatcherTest extends TestCase
{
    private FastRouteDispatcher $dispatcher;

    private Router $router;

    public function testDispatchFound() : void
    {
        $app = AppFactory::create();
        $app->get('/test', static fn() => 'response');
        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
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

    protected function setUp() : void
    {
        $app = AppFactory::create();
        $this->router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $this->dispatcher = $app->getContainer()->get(\Point\Interfaces\DispatcherInterface::class);
    }
}

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

/**
 * @internal
 * @small
 */
final class UrlGeneratorTest extends TestCase
{
    public function testGenerateUrl() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $generator = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);

        $route = $router->map(['GET'], '/user/{id}', static fn() => 'response')->setName('user.profile');

        $url = $generator->generate('user.profile', ['id' => '123']);

        self::assertSame('/user/123', $url);
    }

    public function testGenerateUrlThrowsForUnknownRoute() : void
    {
        $app = AppFactory::create();
        $generator = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $generator->generate('unknown.route', []);
    }

    public function testGenerateUrlWithQueryParams() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(\Point\Interfaces\RouterInterface::class);
        $generator = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);

        $route = $router->map(['GET'], '/search', static fn() => 'response')->setName('search');

        $url = $generator->generate('search', [], ['q' => 'test', 'page' => '2']);

        self::assertSame('/search?q=test&page=2', $url);
    }
}
