<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use FastRoute\DataGenerator\GroupCountBased;

/** @internal */
final class RevisionedRouteDataGenerator extends GroupCountBased
{
    private int $revision = 0;

    /**
     * @param string $httpMethod
     * @param array<mixed> $routeData
     * @param mixed $handler
     */
    public function addRoute($httpMethod, $routeData, $handler) : void
    {
        parent::addRoute($httpMethod, $routeData, $handler);
        // One collector call can register several entries before a later entry throws.
        $this->revision++;
    }

    public function getRevision() : int
    {
        return $this->revision;
    }
}
