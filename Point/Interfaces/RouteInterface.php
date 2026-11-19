<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

namespace Point\Interfaces;

use Point\Routing\RouteGroup;
use Psr\Http\Server\MiddlewareInterface;

interface RouteInterface
{
    /**
     * Get route callable.
     *
     * @return callable|string
     */
    public function getHandler() : callable|string;

    /**
     * Get route HTTP methods
     *
     * @return array<string>
     */
    public function getMethods() : array;

    /**
     * @return array<MiddlewareInterface|callable|string>
     */
    public function getMiddleware() : array;

    /**
     * Get route name.
     */
    public function getName() : ?string;

    /**
     * Get route pattern.
     *
     * @return string
     */
    public function getPattern() : string;

    /**
     * Get route group.
     *
     * @return RouteGroup|null
     */
    public function getRouteGroup() : ?RouteGroup;

    /**
     * Set route name.
     *
     * @param string $name The route name
     *
     * @return RouteInterface
     */
    public function setName(string $name) : self;
}
