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
use Point\Middleware\ContentLengthMiddleware;
use Point\Middleware\HeadMethodMiddleware;
use Point\Middleware\MethodOverrideMiddleware;
use Point\Middleware\OutputBufferingMiddleware;
use Point\Middleware\PlainTextExceptionMiddleware;
use Point\Middleware\ResponseFactoryMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @internal
 * @small
 */
final class MiddlewareTest extends TestCase
{
    public function testAllMiddlewareImplementMiddlewareInterface() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        $middlewares = [
            $container->get(\Point\Middleware\ErrorExceptionMiddleware::class),
            $container->get(\Point\Middleware\HtmlExceptionMiddleware::class),
            $container->get(\Point\Middleware\JsonExceptionMiddleware::class),
            $container->get(\Point\Middleware\ExceptionLoggingMiddleware::class),
            $container->get(\Point\Middleware\RoutingMiddleware::class),
            $container->get(\Point\Middleware\EndpointMiddleware::class),
            $container->get(\Point\Middleware\BasePathMiddleware::class),
            $container->get(ContentLengthMiddleware::class),
            $container->get(HeadMethodMiddleware::class),
            $container->get(MethodOverrideMiddleware::class),
            $container->get(OutputBufferingMiddleware::class),
            $container->get(PlainTextExceptionMiddleware::class),
            $container->get(ResponseFactoryMiddleware::class),
        ];

        foreach ($middlewares as $middleware) {
            self::assertInstanceOf(MiddlewareInterface::class, $middleware);
        }
    }

    private function createRequest(string $method, string $uri) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createServerRequest($method, $uri);
    }

    private function createResponse(int $status = 200) : ResponseInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createResponse($status);
    }
}
