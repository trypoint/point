<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

namespace Point\Routing;

use FastRoute\RouteCollector;
use InvalidArgumentException;
use Point\Interfaces\RouteInterface;
use Point\Interfaces\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Router implements RouterInterface, RequestHandlerInterface
{
    use MiddlewareCollectionTrait;
    use RouteCollectionTrait;

    private ?string $basePath = null;

    private RouteCollector $collector;

    private PipelineRunner $pipelineRunner;

    public function __construct(PipelineRunner $pipelineRunner)
    {
        $this->collector = new RevisionedRouteCollector();
        $this->pipelineRunner = $pipelineRunner;
    }

    public function getBasePath() : ?string
    {
        return $this->basePath;
    }

    public function getRouteCollector() : RouteCollector
    {
        return $this->collector;
    }

    public function group(string $path, callable $handler) : RouteGroup
    {
        $routeGroup = new RouteGroup($path, $handler, $this->getRouteCollector());
        $this->collector->addGroup($path, $routeGroup);

        return $routeGroup;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return $this->pipelineRunner
            ->withPipeline($this->getMiddleware())
            ->handle($request);
    }

    public function map(array $methods, string $path, callable|string $handler) : RouteInterface
    {
        if (! $methods) {
            throw new InvalidArgumentException('HTTP methods array cannot be empty');
        }

        $route = new Route($methods, $path, $handler, null);

        $this->collector->addRoute($methods, $path, $route);

        return $route;
    }

    public function setBasePath(string $basePath) : void
    {
        $this->basePath = $basePath;
    }
}
