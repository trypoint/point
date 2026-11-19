<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

namespace Point\Middleware;

use Point\Exception\HttpMethodNotAllowedException;
use Point\Exception\HttpNotFoundException;
use Point\Interfaces\RouteInterface;
use Point\Routing\PipelineRunner;
use Point\Routing\RouteInvoker;
use Point\Routing\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Interprets the RouteMatch produced by RoutingMiddleware and either:
 * - executes the matched route together with its middleware stack, or
 * - throws the appropriate HTTP exception for 404 / 405 cases.
 *
 * This middleware is intended to be terminal within the routing pipeline.
 */
final class EndpointMiddleware implements MiddlewareInterface
{
    private PipelineRunner $pipelineRunner;

    private RouteInvoker $routeInvoker;

    public function __construct(
        RouteInvoker $routeInvoker,
        PipelineRunner $pipelineRunner,
    ) {
        $this->routeInvoker = $routeInvoker;
        $this->pipelineRunner = $pipelineRunner;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);

        if (! $routeMatch instanceof RouteMatch) {
            throw new RuntimeException(
                'RouteMatch is missing from the request. Add RoutingMiddleware before EndpointMiddleware.',
            );
        }

        if ($routeMatch->isFound()) {
            $route = $routeMatch->getRoute();

            if (! $route instanceof RouteInterface) {
                throw new RuntimeException('RouteMatch is in FOUND state but does not contain a valid route.');
            }

            return $this->handleFound($request, $route, $routeMatch->getArguments());
        }

        if ($routeMatch->isNotFound()) {
            throw new HttpNotFoundException($request);
        }

        if ($routeMatch->isMethodNotAllowed()) {
            $exception = new HttpMethodNotAllowedException($request);
            $exception->setAllowedMethods($routeMatch->getAllowedMethods());

            throw $exception;
        }

        throw new RuntimeException('An unexpected routing state was encountered.');
    }

    /**
     * Collects middleware in execution order:
     * - outermost parent group middleware first
     * - nested group middleware next
     * - route-specific middleware last
     *
     * @return array<MiddlewareInterface|callable|string>
     */
    private function collectRouteMiddleware(RouteInterface $route) : array
    {
        $groupMiddlewareStack = [];
        $group = $route->getRouteGroup();

        while ($group !== null) {
            array_unshift($groupMiddlewareStack, $group->getMiddleware());
            $group = $group->getRouteGroup();
        }

        $pipeline = [];

        foreach ($groupMiddlewareStack as $middlewareList) {
            foreach ($middlewareList as $middleware) {
                $pipeline[] = $middleware;
            }
        }

        foreach ($route->getMiddleware() as $middleware) {
            $pipeline[] = $middleware;
        }

        return $pipeline;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function handleFound(
        ServerRequestInterface $request,
        RouteInterface $route,
        array $arguments,
    ) : ResponseInterface {
        $pipeline = $this->collectRouteMiddleware($route);

        $pipeline[] = $this->routeInvoker->withHandler(
            $route->getHandler(),
            $arguments,
        );

        return $this->pipelineRunner
            ->withPipeline($pipeline)
            ->handle($request);
    }
}
