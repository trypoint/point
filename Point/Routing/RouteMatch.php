<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

namespace Point\Routing;

use Point\Interfaces\DispatcherInterface;
use Point\Interfaces\RouteInterface;

final class RouteMatch
{
    /** @var list<string> */
    private array $allowedMethods;

    /** @var array<string, mixed> */
    private array $arguments;

    private ?RouteInterface $route;

    private int $status;

    /**
     * @param array<string, mixed> $arguments
     * @param list<string> $allowedMethods
     */
    private function __construct(
        int $status,
        ?RouteInterface $route = null,
        array $arguments = [],
        array $allowedMethods = [],
    ) {
        $this->status = $status;
        $this->route = $route;
        $this->arguments = $arguments;
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public static function found(
        RouteInterface $route,
        array $arguments = [],
    ) : self {
        return new self(DispatcherInterface::FOUND, $route, $arguments, []);
    }

    /**
     * @return list<string>
     */
    public function getAllowedMethods() : array
    {
        return $this->allowedMethods;
    }

    public function getArgument(string $name, mixed $default = null) : mixed
    {
        return $this->arguments[$name] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArguments() : array
    {
        return $this->arguments;
    }

    public function getRoute() : ?RouteInterface
    {
        return $this->route;
    }

    public function getStatus() : int
    {
        return $this->status;
    }

    public function isFound() : bool
    {
        return $this->status === DispatcherInterface::FOUND;
    }

    public function isMethodNotAllowed() : bool
    {
        return $this->status === DispatcherInterface::METHOD_NOT_ALLOWED;
    }

    public function isNotFound() : bool
    {
        return $this->status === DispatcherInterface::NOT_FOUND;
    }

    /**
     * @param list<string> $allowedMethods
     */
    public static function methodNotAllowed(array $allowedMethods) : self
    {
        return new self(DispatcherInterface::METHOD_NOT_ALLOWED, null, [], $allowedMethods);
    }

    public static function notFound() : self
    {
        return new self(DispatcherInterface::NOT_FOUND, null, [], []);
    }
}
