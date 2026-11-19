<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Simple PSR-11 container implementation for Point framework.
 *
 * This container provides a minimal dependency injection container
 * that doesn't require external dependencies like PHP-DI.
 * It supports:
 * - Service definitions as callables (factory functions)
 * - Service definitions as direct values (instances)
 * - Singleton pattern (services are cached after first creation)
 */
final class SimpleContainer implements ContainerInterface
{
    /**
     * @var array<string, callable(ContainerInterface): mixed|object|array|string|int|float|bool|null>
     */
    /** @phpstan-ignore missingType.iterableValue */
    private array $definitions = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @param array<string, callable(ContainerInterface): mixed|object|array|string|int|float|bool|null> $definitions
     */
    /** @phpstan-ignore missingType.iterableValue */
    public function __construct(array $definitions = [])
    {
        foreach ($definitions as $id => $definition) {
            $this->set($id, $definition);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id) : mixed
    {
        if (! $this->has($id)) {
            throw new class ($id) extends Exception implements NotFoundExceptionInterface {
                public function __construct(string $id)
                {
                    parent::__construct(sprintf('Service "%s" not found.', $id));
                }
            };
        }

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $definition = $this->definitions[$id];

        if (is_callable($definition)) {
            $instance = $definition($this);
        } else {
            $instance = $definition;
        }

        $this->instances[$id] = $instance;

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id) : bool
    {
        return isset($this->definitions[$id]) || isset($this->instances[$id]);
    }

    /**
     * Set a service definition.
     *
     * @param string $id
     * @param callable(ContainerInterface): mixed|object|array|string|int|float|bool|null $definition
     * @return self
     */
    /** @phpstan-ignore missingType.iterableValue */
    public function set(string $id, callable|object|array|string|int|float|bool|null $definition) : self
    {
        $this->definitions[$id] = $definition;
        unset($this->instances[$id]);

        return $this;
    }
}
