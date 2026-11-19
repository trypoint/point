<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

namespace Point\Routing;

use Point\Interfaces\MiddlewareCollectionInterface;
use Point\Interfaces\RouteInterface;

final class Route implements RouteInterface, MiddlewareCollectionInterface
{
    use MiddlewareCollectionTrait;

    /**
     * Parent route group
     */
    private ?RouteGroup $group;

    /**
     * @var callable|string
     */
    private $handler;

    /**
     * @var array<string>
     */
    private array $methods;

    /**
     * Route name
     */
    private ?string $name = null;

    /**
     * The route matching pattern
     */
    private string $pattern;

    /**
     * @param array<string> $methods
     * @param string $pattern
     * @param callable|string $handler
     * @param ?RouteGroup $group
     */
    public function __construct(array $methods, string $pattern, callable|string $handler, ?RouteGroup $group = null)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->group = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler() : callable|string
    {
        return $this->handler;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods() : array
    {
        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern() : string
    {
        return $this->pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteGroup() : ?RouteGroup
    {
        return $this->group;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }
}
