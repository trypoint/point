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
use Point\Middleware\MethodOverrideMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @small
 */
final class MethodOverrideMiddlewareTest extends TestCase
{
    public function testDoesNotOverrideForGetRequest() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(MethodOverrideMiddleware::class);
        $request = $this->createRequest('GET', '/', ['_method' => 'DELETE']);

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

                return $factory->createResponse(200)
                    ->withHeader('X-Method', $request->getMethod());
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals('GET', $response->getHeaderLine('X-Method'));
    }

    public function testOverridesMethodViaPostParameter() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(MethodOverrideMiddleware::class);
        $request = $this->createRequest('POST', '/', ['_method' => 'DELETE']);

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

                return $factory->createResponse(200)
                    ->withHeader('X-Method', $request->getMethod());
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals('DELETE', $response->getHeaderLine('X-Method'));
    }

    private function createRequest(string $method, string $uri, array $params = []) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $request = $factory->createServerRequest($method, $uri);
        if (! empty($params)) {
            $parsedBody = $params;
            $request = $request->withParsedBody($parsedBody);
        }

        return $request;
    }
}
