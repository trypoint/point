<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareCollectionTrait
{
    /**
     * @var array<MiddlewareInterface|callable|string>
     */
    private array $middleware = [];

    public function add(MiddlewareInterface|callable|string $middleware) : self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function addMiddleware(MiddlewareInterface $middleware) : self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * @return array<MiddlewareInterface|callable|string>
     */
    public function getMiddleware() : array
    {
        return $this->middleware;
    }
}
