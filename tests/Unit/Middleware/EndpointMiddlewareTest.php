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
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 * @small
 */
final class EndpointMiddlewareTest extends TestCase
{
    public function testExecutesMatchedRoute() : void
    {
        $app = AppFactory::create();
        $app->get('/test', static function ($req, $res, array $args) {
            $res->getBody()->write('Hello');

            return $res;
        });
        $container = $app->getContainer();

        $request = $this->createRequest('GET', '/test');

        // Use the app's pipeline runner to properly run the middleware
        $pipelineRunner = $container->get(\Point\Routing\PipelineRunner::class);
        $pipeline = [
            $container->get(\Point\Middleware\RoutingMiddleware::class),
            $container->get(\Point\Middleware\EndpointMiddleware::class),
        ];

        $response = $pipelineRunner->withPipeline($pipeline)->handle($request);
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Hello', (string)$response->getBody());
    }

    public function testThrowsMethodNotAllowedForWrongMethod() : void
    {
        $app = AppFactory::create();
        $app->get('/test', static function ($req, $res, array $args) {
            return $res;
        });
        $container = $app->getContainer();

        $request = $this->createRequest('POST', '/test');

        $pipelineRunner = $container->get(\Point\Routing\PipelineRunner::class);
        $pipeline = [
            $container->get(\Point\Middleware\RoutingMiddleware::class),
            $container->get(\Point\Middleware\EndpointMiddleware::class),
        ];

        $this->expectException(\Point\Exception\HttpMethodNotAllowedException::class);
        $pipelineRunner->withPipeline($pipeline)->handle($request);
    }

    public function testThrowsNotFoundForUnmatchedRoute() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        $request = $this->createRequest('GET', '/nonexistent');

        $pipelineRunner = $container->get(\Point\Routing\PipelineRunner::class);
        $pipeline = [
            $container->get(\Point\Middleware\RoutingMiddleware::class),
            $container->get(\Point\Middleware\EndpointMiddleware::class),
        ];

        $this->expectException(\Point\Exception\HttpNotFoundException::class);
        $pipelineRunner->withPipeline($pipeline)->handle($request);
    }

    private function createRequest(string $method, string $uri) : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createServerRequest($method, $uri);
    }
}
