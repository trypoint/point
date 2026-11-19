<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
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
        $generator = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);

        $url = $generator->urlFor('user.profile', ['id' => '123']);
        self::assertEquals('/user/123', $url);
    }

    public function testUrlGenerationWithQueryParams() : void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        $app->get('/search', static fn($req, $res) => $res)->setName('search');
        $generator = $app->getContainer()->get(\Point\Interfaces\UrlGeneratorInterface::class);

        $url = $generator->urlFor('search', [], ['q' => 'test', 'page' => '2']);
        self::assertEquals('/search?q=test&page=2', $url);
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
