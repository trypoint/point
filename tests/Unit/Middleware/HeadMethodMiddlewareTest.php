<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Middleware\HeadMethodMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @small
 */
final class HeadMethodMiddlewareTest extends TestCase
{
    public function testPreservesBodyForNonHeadRequest() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(HeadMethodMiddleware::class);
        $request = $this->createRequest('GET', '/');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
                $response = $factory->createResponse(200);
                $response->getBody()->write('Body content');

                return $response;
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Body content', (string)$response->getBody());
    }

    public function testRemovesBodyForHeadRequest() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(HeadMethodMiddleware::class);
        $request = $this->createRequest('HEAD', '/');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
                $response = $factory->createResponse(200);
                $response->getBody()->write('Body content');

                return $response;
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('', (string)$response->getBody());
    }

    private function createRequest(string $method, string $uri) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createServerRequest($method, $uri);
    }
}
