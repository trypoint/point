<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Point\Middleware\ErrorExceptionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @small
 */
final class ErrorExceptionMiddlewareTest extends TestCase
{
    public function testConvertsPhpErrorsToErrorException() : void
    {
        $middleware = new ErrorExceptionMiddleware();
        $request = $this->createRequest('GET', '/');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                trigger_error('Test error', E_USER_WARNING);
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

                return $factory->createResponse(200);
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testIgnoresSilencedErrors() : void
    {
        $middleware = new ErrorExceptionMiddleware();
        $request = $this->createRequest('GET', '/');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                @trigger_error('Silenced error', E_USER_WARNING);
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

                return $factory->createResponse(200);
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testRestoresErrorHandler() : void
    {
        $middleware = new ErrorExceptionMiddleware();
        $request = $this->createRequest('GET', '/');

        $testHandler = static fn() => null;
        set_error_handler($testHandler);

        try {
            $handler = new class () implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request) : ResponseInterface
                {
                    return (new \Nyholm\Psr7\Factory\Psr17Factory())->createResponse(200);
                }
            };

            $middleware->process($request, $handler);

            // After middleware runs, error handler should be restored to our test handler
            $restoredHandler = set_error_handler(static fn() => null);
            self::assertSame($testHandler, $restoredHandler);
        } finally {
            // Pop both handlers we pushed onto the stack (test handler + null handler)
            restore_error_handler();
            restore_error_handler();
        }
    }

    private function createRequest(string $method, string $uri) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createServerRequest($method, $uri);
    }
}
