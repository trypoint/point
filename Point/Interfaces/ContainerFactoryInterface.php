<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Interfaces;

use Psr\Container\ContainerInterface;

/**
 * Factory interface for creating a service container.
 */
interface ContainerFactoryInterface
{
    /**
     * Create a container instance.
     *
     * @param array<string, mixed> $definitions The service definitions.
     *
     * @return ContainerInterface
     */
    public function createContainer(array $definitions = []) : ContainerInterface;
}
