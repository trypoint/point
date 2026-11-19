<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Interfaces;

interface ContainerResolverInterface
{
    /**
     * Resolve the given $identifier to an object.
     *
     * @param callable|object|array<string|object,string>|string $identifier
     *
     * @return mixed An object or callable
     */
    public function resolve(callable|object|array|string $identifier) : mixed;

    /**
     * Resolve the given $identifier to a callable that is bounded to the container.
     *
     * @param callable|string|array<string|object,string> $identifier
     *
     * @return callable A callable
     */
    public function resolveCallable(callable|array|string $identifier) : callable;
}
