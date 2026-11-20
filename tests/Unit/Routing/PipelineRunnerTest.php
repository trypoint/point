<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Routing;

use Closure;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Point\Interfaces\ContainerResolverInterface;
use Point\Routing\PipelineRunner;
use Point\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * @internal
 * @small
 */
final class PipelineRunnerTest extends TestCase
{
    private function createRequest(string $method = 'GET', string $uri = '/') : ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest($method, $uri);
    }

    private function createResponse(int $status = 200) : ResponseInterface
    {
        return (new Psr17Factory())->createResponse($status);
    }

    private function newRunner() : PipelineRunner
    {
        $resolver = new class implements ContainerResolverInterface {
            public function resolve(callable|object|array|string $identifier) : mixed
            {
                return $identifier;
            }

            public function resolveCallable(callable|array|string $identifier) : callable
            {
                return $identifier instanceof Closure ? $identifier : static function () : void {};
            }
        };

        return new PipelineRunner($resolver);
    }

    public function testRouterCreatesIsolatedPipelinePerRequest() : void
    {
        $runner = $this->newRunner();
        $router = new Router($runner);

        $middlewareRan = 0;
        $terminal = static function () : ResponseInterface {
            return (new Psr17Factory())->createResponse(200);
        };

        $router->add(static function ($req, $handler) use (&$middlewareRan, $terminal) : ResponseInterface {
            $middlewareRan++;

            return $handler->handle($req);
        });

        // Register the terminal handler so the pipeline has a next entry.
        $runner = $this->newRunner();
        $pipeline = [
            static function ($req, $handler) use (&$middlewareRan) : ResponseInterface {
                $middlewareRan++;

                return $handler->handle($req);
            },
            $terminal,
        ];

        $request = $this->createRequest();

        $response1 = $runner->withPipeline($pipeline)->handle($request);
        $response2 = $runner->withPipeline($pipeline)->handle($request);

        self::assertEquals(200, $response1->getStatusCode());
        self::assertEquals(200, $response2->getStatusCode());
        self::assertEquals(2, $middlewareRan, 'Each request must start at pipeline index 0.');
    }

    public function testConsecutiveRequestsThroughSameRouter() : void
    {
        $router = new Router($this->newRunner());
        $middlewareRan = 0;

        $router->add(static function ($req, $handler) use (&$middlewareRan) : ResponseInterface {
            $middlewareRan++;

            return $handler->handle($req);
        });
        $router->add(static function () : ResponseInterface {
            return (new Psr17Factory())->createResponse(200);
        });

        $request = $this->createRequest();

        $response1 = $router->handle($request);
        $response2 = $router->handle($request);

        self::assertEquals(200, $response1->getStatusCode());
        self::assertEquals(200, $response2->getStatusCode());
        self::assertEquals(2, $middlewareRan, 'Router::handle() must start each request at pipeline index 0.');
    }

    public function testNormalMiddlewareNestingAtoBtoEndpoint() : void
    {
        $runner = $this->newRunner();
        $order = [];

        $endpoint = static function () use (&$order) : ResponseInterface {
            $order[] = 'endpoint';

            return (new Psr17Factory())->createResponse(200);
        };

        $pipeline = [
            static function ($req, $handler) use (&$order) : ResponseInterface {
                $order[] = 'A-in';
                $response = $handler->handle($req);
                $order[] = 'A-out';

                return $response;
            },
            static function ($req, $handler) use (&$order) : ResponseInterface {
                $order[] = 'B-in';
                $response = $handler->handle($req);
                $order[] = 'B-out';

                return $response;
            },
            $endpoint,
        ];

        $response = $runner->withPipeline($pipeline)->handle($this->createRequest());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['A-in', 'B-in', 'endpoint', 'B-out', 'A-out'], $order);
    }

    public function testMiddlewareShortCircuits() : void
    {
        $runner = $this->newRunner();
        $ran = [];

        $pipeline = [
            static function ($req, $handler) use (&$ran) : ResponseInterface {
                $ran[] = 'short-circuit';

                return (new Psr17Factory())->createResponse(403);
            },
            static function () use (&$ran) : ResponseInterface {
                $ran[] = 'should-not-run';

                return (new Psr17Factory())->createResponse(200);
            },
        ];

        $response = $runner->withPipeline($pipeline)->handle($this->createRequest());

        self::assertEquals(403, $response->getStatusCode());
        self::assertEquals(['short-circuit'], $ran);
    }

    public function testExceptionPropagates() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $runner = $this->newRunner();

        $pipeline = [
            static function () : ResponseInterface {
                throw new RuntimeException('boom');
            },
            static function () : ResponseInterface {
                return (new Psr17Factory())->createResponse(200);
            },
        ];

        $runner->withPipeline($pipeline)->handle($this->createRequest());
    }

    public function testDirectPipelineRunnerReuseThrowsOnSecondRequest() : void
    {
        $runner = $this->newRunner();

        $pipeline = [
            static function ($req, $handler) : ResponseInterface {
                return $handler->handle($req);
            },
            static function () : ResponseInterface {
                return (new Psr17Factory())->createResponse(200);
            },
        ];

        $first = $runner->withPipeline($pipeline)->handle($this->createRequest());
        self::assertEquals(200, $first->getStatusCode());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The middleware pipeline is empty or exhausted.');

        // Reusing the same instance without withPipeline() resumes at the exhausted index.
        $runner->handle($this->createRequest());
    }

    public function testMiddlewareInvokingNextHandlerMoreThanOnceConsumesMultipleEntries() : void
    {
        $runner = $this->newRunner();
        $ran = [];

        $entry2 = static function () use (&$ran) : ResponseInterface {
            $ran[] = 'entry2';

            return (new Psr17Factory())->createResponse(202);
        };

        $entry3 = static function () use (&$ran) : ResponseInterface {
            $ran[] = 'entry3';

            return (new Psr17Factory())->createResponse(203);
        };

        $pipeline = [
            static function ($req, $handler) use (&$ran, $entry2, $entry3) : ResponseInterface {
                $ran[] = 'mw-first-call';
                $handler->handle($req); // advances index to entry2
                $ran[] = 'mw-second-call';
                $handler->handle($req); // advances index to entry3

                return (new Psr17Factory())->createResponse(200);
            },
            $entry2,
            $entry3,
        ];

        $response = $runner->withPipeline($pipeline)->handle($this->createRequest());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['mw-first-call', 'entry2', 'mw-second-call', 'entry3'], $ran);
    }
}
