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
use RuntimeException;

/**
 * @internal
 * @small
 */
final class MiddlewareIntegrationTest extends TestCase
{
    public function testErrorMiddlewareStack() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/error', static fn() => throw new RuntimeException('Test error'));

        $request = $this->createRequest('GET', '/error', ['Accept' => 'application/json']);
        $response = $app->handle($request);

        self::assertEquals(500, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        self::assertArrayHasKey('message', $body);
        self::assertArrayHasKey('exception', $body);
    }

    public function testExceptionPropagation() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);

        $app->add(static function ($req, $handler) : void {
            throw new RuntimeException('Middleware exception');
        });

        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('GET', '/', ['Accept' => 'application/json']);
        $response = $app->handle($request);

        self::assertEquals(500, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        self::assertEquals('Application Error', $body['message']);
        self::assertArrayHasKey('exception', $body);
        self::assertStringContainsString('Middleware exception', $body['exception'][0]['message']);
    }

    public function testHtmlErrorResponse() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $app->addRoutingMiddleware();
        $app->get('/error', static fn() => throw new RuntimeException('Test error'));

        $request = $this->createRequest('GET', '/error', ['Accept' => 'text/html']);
        $response = $app->handle($request);

        self::assertEquals(500, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('Test error', (string)$response->getBody());
    }

    public function testIndependentRequestHandling() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);

        $counter = 0;
        $app->add(static function ($req, $handler) use (&$counter) {
            $counter++;

            return $handler->handle($req);
        });

        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request1 = $this->createRequest('GET', '/');
        $request2 = $this->createRequest('GET', '/');

        $app->handle($request1);
        $app->handle($request2);

        self::assertEquals(2, $counter);
    }

    public function testMiddlewareShortCircuit() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);

        $app->add(static function ($req, $handler) {
            $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $response = $factory->createResponse(403);
            $response->getBody()->write('Forbidden');

            return $response;
        });

        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        self::assertEquals(403, $response->getStatusCode());
        self::assertEquals('Forbidden', (string)$response->getBody());
    }

    public function testRequestModificationMiddleware() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);

        $app->add(static function ($req, $handler) {
            return $handler->handle($req->withAttribute('custom', 'value'));
        });

        $app->addRoutingMiddleware();
        $app->get('/', static function ($req, $res) {
            $res->getBody()->write($req->getAttribute('custom', 'default'));

            return $res;
        });

        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        self::assertEquals('value', (string)$response->getBody());
    }

    public function testResponseModificationMiddleware() : void
    {
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);

        $app->add(static function ($req, $handler) {
            $response = $handler->handle($req);

            return $response->withHeader('X-Modified', 'yes');
        });

        $app->addRoutingMiddleware();
        $app->get('/', static fn($req, $res) => $res);

        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        self::assertEquals('yes', $response->getHeaderLine('X-Modified'));
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
