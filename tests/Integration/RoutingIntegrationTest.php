<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Interfaces\UrlGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 * @small
 */
final class RoutingIntegrationTest extends TestCase
{
    public function test404NotFound() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('GET', '/nonexistent');
        $response = $app->handle($request);

        self::assertEquals(404, $response->getStatusCode());
    }

    public function test405MethodNotAllowed() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('POST', '/');
        $response = $app->handle($request);

        self::assertEquals(405, $response->getStatusCode());
        self::assertStringContainsString('GET', $response->getHeaderLine('Allow'));
    }

    public function testAllowHeaderContainsAllAllowedMethods() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/test', static fn($req, $res) => $res);
        $app->post('/test', static fn($req, $res) => $res);
        $app->put('/test', static fn($req, $res) => $res);

        $request = $this->createRequest('DELETE', '/test');
        $response = $app->handle($request);

        self::assertEquals(405, $response->getStatusCode());
        $allow = $response->getHeaderLine('Allow');
        self::assertStringContainsString('GET', $allow);
        self::assertStringContainsString('POST', $allow);
        self::assertStringContainsString('PUT', $allow);
    }

    public function testDynamicRouteParameters() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/user/{id}/post/{postId}', static function ($req, $res, $args) {
            $res->getBody()->write("User: {$args['id']}, Post: {$args['postId']}");

            return $res;
        });

        $request = $this->createRequest('GET', '/user/123/post/456');
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('User: 123, Post: 456', (string)$response->getBody());
    }

    public function testOptionalParameters() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/blog[/{year}[/{month}]]', static function ($req, $res, $args) {
            $res->getBody()->write(json_encode($args));

            return $res;
        });

        $request = $this->createRequest('GET', '/blog/2024/01');
        $response = $app->handle($request);

        self::assertEquals(200, $response->getStatusCode());
        $args = json_decode((string)$response->getBody(), true);
        self::assertEquals('2024', $args['year'] ?? '');
        self::assertEquals('01', $args['month'] ?? '');
    }

    public function testUrlGeneration() : void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        $app->get('/user/{id}', static fn($req, $res) => $res)->setName('user.profile');
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        $url = $generator->urlFor('user.profile', ['id' => '123']);
        self::assertEquals('/user/123', $url);
    }

    public function testUrlGenerationWithQueryParams() : void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        $app->get('/search', static fn($req, $res) => $res)->setName('search');
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        $url = $generator->urlFor('search', [], ['q' => 'test', 'page' => '2']);
        self::assertEquals('/search?q=test&page=2', $url);
    }

    public static function routeParameters() : iterable
    {
        yield 'space' => ['a b', 'a%20b'];
        yield 'question mark' => ['a?b', 'a%3Fb'];
        yield 'hash' => ['a#b', 'a%23b'];
        yield 'percent' => ['a%b', 'a%25b'];
        yield 'literal encoded slash' => ['%2F', '%252F'];
        yield 'UTF-8 and plus' => ['café+', 'caf%C3%A9%2B'];
    }

    #[DataProvider('routeParameters')]
    public function testRouteParameterEncodingRoundTrips(string $value, string $encoded) : void
    {
        $app = AppFactory::create();
        $app->get('/item/{id}[/details]', static function ($request, $response, $args) {
            $response->getBody()->write($args['id']);

            return $response;
        })->setName('item');
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);
        $url = $generator->urlFor('item', ['id' => $value], ['view' => 'short']);

        self::assertSame('/item/' . $encoded . '/details?view=short', $url);
        $response = $app->handle($this->createRequest('GET', $url));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame($value, (string)$response->getBody());
    }

    public function testEncodedSlashCannotIntroducePathSegments() : void
    {
        $app = AppFactory::create();
        $app->get('/item/{id}', static fn($request, $response) => $response);
        $app->get('/item/{category}/{id}', static fn($request, $response) => $response);

        foreach (['/item/a%2Fb', '/item/a%2fb'] as $path) {
            self::assertSame(404, $app->handle($this->createRequest('GET', $path))->getStatusCode());
        }
        self::assertSame(200, $app->handle($this->createRequest('GET', '/item/a/b'))->getStatusCode());
    }

    public function testRouteRenameUpdatesDuplicateWinnerWithoutAnotherLookup() : void
    {
        $app = AppFactory::create();
        $handler = static fn($request, $response) => $response;
        $earlier = $app->get('/earlier', $handler)->setName('original');
        $app->get('/later', $handler)->setName('shared');
        $generator = $app->getContainer()->get(UrlGeneratorInterface::class);

        self::assertSame('/later', $generator->urlFor('shared'));
        $earlier->setName('shared');
        self::assertSame('/earlier', $generator->urlFor('shared'));
        $earlier->setName('original');
        self::assertSame('/later', $generator->urlFor('shared'));
        self::assertSame('/earlier', $generator->urlFor('original'));
    }

    private function createRequest(string $method, string $uri, array $headers = []) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $factory->createServerRequest($method, $uri);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
