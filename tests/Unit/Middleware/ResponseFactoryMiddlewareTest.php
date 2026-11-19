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
use Point\Middleware\ResponseFactoryMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @small
 */
final class ResponseFactoryMiddlewareTest extends TestCase
{
    public function testCreatesEmptyResponse() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(ResponseFactoryMiddleware::class);
        $request = $this->createRequest('GET', '/');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                return (new \Nyholm\Psr7\Factory\Psr17Factory())->createResponse(200);
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals(200, $response->getStatusCode());
    }

    private function createRequest(string $method, string $uri) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createServerRequest($method, $uri);
    }
}
