<?php

namespace Point\Interfaces;

use FastRoute\RouteCollector;
use InvalidArgumentException;
use Point\Routing\RouteGroup;
use Psr\Http\Server\MiddlewareInterface;

interface RouterInterface
{
    public function add(MiddlewareInterface|callable|string $middleware) : self;

    public function addMiddleware(MiddlewareInterface $middleware) : self;

    public function any(string $pattern, callable|string $handler) : RouteInterface;

    public function delete(string $path, callable|string $handler) : RouteInterface;

    public function get(string $path, callable|string $handler) : RouteInterface;

    public function getBasePath() : ?string;

    /**
     * @return array<MiddlewareInterface|callable|string>
     */
    public function getMiddleware() : array;

    public function getRouteCollector() : RouteCollector;

    public function group(string $path, callable $handler) : RouteGroup;

    /**
     * @param array<string> $methods
     * @param string $path
     * @param callable|string $handler
     *
     * @throws InvalidArgumentException
     */
    public function map(array $methods, string $path, callable|string $handler) : RouteInterface;

    public function options(string $path, callable|string $handler) : RouteInterface;

    public function patch(string $path, callable|string $handler) : RouteInterface;

    public function post(string $path, callable|string $handler) : RouteInterface;

    public function put(string $path, callable|string $handler) : RouteInterface;

    public function query(string $path, callable|string $handler) : RouteInterface;

    public function setBasePath(string $basePath) : void;
}
