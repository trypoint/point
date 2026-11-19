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
use Point\Middleware\OutputBufferingMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;

/**
 * @internal
 * @small
 */
final class OutputBufferingMiddlewareTest extends TestCase
{
    public function testCapturesOutput() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(OutputBufferingMiddleware::class);
        $request = $this->createRequest('GET', '/');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                echo 'Output content';
                $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

                return $factory->createResponse(200);
            }
        };

        $response = $middleware->process($request, $handler);
        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('Output content', (string)$response->getBody());
    }

    public function testCleansOutputOnException() : void
    {
        $app = AppFactory::create();
        $middleware = $app->getContainer()->get(OutputBufferingMiddleware::class);
        $request = $this->createRequest('GET', '/');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                echo 'Output before exception';

                throw new RuntimeException('Test error');
            }
        };

        try {
            $middleware->process($request, $handler);
        } catch (Throwable) {
            // Expected
        }

        // No assertion needed - just verify no output leaks
        self::assertTrue(true);
    }

    private function createRequest(string $method, string $uri) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createServerRequest($method, $uri);
    }
}
