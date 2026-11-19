<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use Point\Interfaces\RouteInterface;

trait RouteCollectionTrait
{
    public function any(string $pattern, callable|string $handler) : RouteInterface
    {
        return $this->map(['*'], $pattern, $handler);
    }

    public function delete(string $path, callable|string $handler) : RouteInterface
    {
        return $this->map(['DELETE'], $path, $handler);
    }

    public function get(string $path, callable|string $handler) : RouteInterface
    {
        return $this->map(['GET'], $path, $handler);
    }

    abstract public function group(string $path, callable $handler) : RouteGroup;

    abstract public function map(array $methods, string $path, callable|string $handler) : RouteInterface;

    public function options(string $path, callable|string $handler) : RouteInterface
    {
        return $this->map(['OPTIONS'], $path, $handler);
    }

    public function patch(string $path, callable|string $handler) : RouteInterface
    {
        return $this->map(['PATCH'], $path, $handler);
    }

    public function post(string $path, callable|string $handler) : RouteInterface
    {
        return $this->map(['POST'], $path, $handler);
    }

    public function put(string $path, callable|string $handler) : RouteInterface
    {
        return $this->map(['PUT'], $path, $handler);
    }

    public function query(string $path, callable|string $handler) : RouteInterface
    {
        return $this->map(['QUERY'], $path, $handler);
    }
}
