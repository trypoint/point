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

    private bool $collecting = false;

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
        if ($this->routeCollector instanceof RevisionedRouteCollector) {
            $this->routeCollector->withGroupPrefix($this->prefix, fn() => ($this->callback)($this));

            return;
        }

        $previous = $this->collecting;
        $this->collecting = true;
        try {
            ($this->callback)($this);
        } finally {
            $this->collecting = $previous;
        }
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

        if ($this->routeCollector instanceof RevisionedRouteCollector) {
            $routeGroup();
        } else {
            $this->routeCollector->addGroup($this->registrationPath($path), $routeGroup);
        }

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
        if ($this->routeCollector instanceof RevisionedRouteCollector) {
            $this->routeCollector->withGroupPrefix(
                $this->prefix,
                fn() => $this->routeCollector->addRoute($methods, $path, $route),
            );
        } else {
            $this->routeCollector->addRoute($methods, $this->registrationPath($path), $route);
        }

        return $route;
    }

    private function registrationPath(string $path) : string
    {
        // A plain FastRoute collector applies the prefix of the enclosing Point callback.
        for ($group = $this; $group !== null; $group = $group->group) {
            if ($group->collecting) {
                return substr($this->prefix, strlen($group->prefix)) . $path;
            }
        }

        return $this->prefix . $path;
    }
}
