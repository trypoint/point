<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container;

use Point\Container\Definition\HttpDefinitions;
use Point\Container\Definition\PointDefinitions;
use Point\Interfaces\ContainerFactoryInterface;
use Psr\Container\ContainerInterface;

final class DiContainerFactory implements ContainerFactoryInterface
{
    public function createContainer(array $definitions = []) : ContainerInterface
    {
        return new SimpleContainer(
            array_replace(
                (new PointDefinitions())->getDefinitions(),
                (new HttpDefinitions())->getDefinitions(),
                $definitions
            )
        );
    }
}
