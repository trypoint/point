<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Routing;

use HttpSoft\Message\Uri;
use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Interfaces\RouteInterface;
use Point\Interfaces\RouterInterface;
use Point\Interfaces\UrlGeneratorInterface;
use UnexpectedValueException;

/**
 * @internal
 * @small
 */
final class UrlGeneratorTest extends TestCase
{
    public function testGenerateUrl() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(RouterInterface::class);
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        $router->map(['GET'], '/user/{id}', static fn() => 'response')->setName('user.profile');

        $url = $generator->urlFor('user.profile', ['id' => '123']);

        self::assertSame('/user/123', $url);
    }

    public function testGenerateUrlThrowsForUnknownRoute() : void
    {
        $app = AppFactory::create();
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        $this->expectException(UnexpectedValueException::class);
        $generator->urlFor('unknown.route', []);
    }

    public function testGenerateUrlWithQueryParams() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(RouterInterface::class);
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        $router->map(['GET'], '/search', static fn() => 'response')->setName('search');

        $url = $generator->urlFor('search', [], ['q' => 'test', 'page' => '2']);

        self::assertSame('/search?q=test&page=2', $url);
    }

    public function testDuplicateNameUsesStaticBeforeDynamicThenFirstDynamic() : void
    {
        $app = AppFactory::create();
        $handler = static fn($request, $response) => $response;
        $app->get('/first/{id}', $handler)->setName('shared');
        $app->get('/second/{id}', $handler)->setName('shared');
        $static = $app->get('/static', $handler)->setName('shared');
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        self::assertSame('/static', $generator->urlFor('shared', ['id' => '42']));
        $static->setName('static');
        self::assertSame('/first/42', $generator->urlFor('shared', ['id' => '42']));
    }

    public function testDirectCollectorHandlerNameIsReadAfterWarmLookup() : void
    {
        $app = AppFactory::create();
        $router = $app->getContainer()->get(RouterInterface::class);
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);
        $name = 'custom';
        $route = $this->createStub(RouteInterface::class);
        $route->method('getPattern')->willReturn('/custom');
        $route->method('getName')->willReturnCallback(static function () use (&$name) : string {
            return $name;
        });
        $router->getRouteCollector()->addRoute('GET', '/custom', $route);

        self::assertSame('/custom', $generator->urlFor('custom'));
        $name = 'renamed';
        self::assertSame('/custom', $generator->urlFor('renamed'));
        $this->expectException(UnexpectedValueException::class);
        $generator->urlFor('custom');
    }

    public function testLateRouteRegistrationAfterWarmLookup() : void
    {
        $app = AppFactory::create();
        $handler = static fn($request, $response) => $response;
        $app->get('/initial', $handler)->setName('initial');
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        self::assertSame('/initial', $generator->urlFor('initial'));
        $app->get('/late', $handler)->setName('late');
        self::assertSame('/late', $generator->urlFor('late'));
    }

    public function testOptionalSegmentsInGeneratedUrls() : void
    {
        $app = AppFactory::create();
        $app->get('/item/{id}[/{section}]', static fn($request, $response) => $response)->setName('item');
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        self::assertSame('/item/42', $generator->urlFor('item', ['id' => '42']));
        self::assertSame('/item/42/details', $generator->urlFor('item', ['id' => '42', 'section' => 'details']));
    }

    public function testRelativeAndFullUrlsIncludeConfiguredBasePath() : void
    {
        $app = AppFactory::create();
        $app->get('/item', static fn($request, $response) => $response)->setName('item');
        $app->getContainer()->get(RouterInterface::class)->setBasePath('/base');
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        self::assertSame('/base/item', $generator->relativeUrlFor('item'));
        self::assertSame('https://example.test:8443/base/item', $generator->fullUrlFor(new Uri('https://example.test:8443'), 'item'));
    }
}
