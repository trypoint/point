<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use FastRoute\RouteCollector;
use Point\Interfaces\MiddlewareCollectionInterface;
use Point\Interfaces\RouteCollectionInterface;
use Point\Interfaces\RouteInterface;

final class RouteGroup implements MiddlewareCollectionInterface, RouteCollectionInterface
{
    use MiddlewareCollectionTrait;

    use RouteCollectionTrait;

    /**
     * @var callable
     */
    private $callback;

    private ?RouteGroup $group;

    private string $prefix;

    private RouteCollector $routeCollector;

    public function __construct(
        string $prefix,
        callable $callback,
        RouteCollector $routeCollector,
        ?self $group = null,
    ) {
        $this->prefix = $prefix;
        $this->callback = $callback;
        $this->routeCollector = $routeCollector;
        $this->group = $group;
    }

    public function __invoke() : void
    {
        // This will be invoked by FastRoute to collect the route groups
        ($this->callback)($this);
    }

    public function getPrefix() : string
    {
        return $this->prefix;
    }

    /**
     * Get parent route group.
     */
    public function getRouteGroup() : ?self
    {
        return $this->group;
    }

    public function group(string $path, callable $handler) : self
    {
        $routePath = $this->prefix . $path;
        $routeGroup = new self($routePath, $handler, $this->routeCollector, $this);

        $this->routeCollector->addGroup($path, $routeGroup);

        return $routeGroup;
    }

    /**
     * @param array<string> $methods
     * @param string $path
     * @param callable|string $handler
     */
    public function map(array $methods, string $path, callable|string $handler) : RouteInterface
    {
        $routePath = $this->prefix . $path;
        $route = new Route($methods, $routePath, $handler, $this);
        $this->routeCollector->addRoute($methods, $path, $route);

        return $route;
    }
}
