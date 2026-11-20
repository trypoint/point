<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;

/** @internal */
final class RevisionedRouteCollector extends RouteCollector
{
    // Collector clones share the FastRoute generator and its revision.
    private readonly RevisionedRouteDataGenerator $routeDataGenerator;

    public function __construct()
    {
        $this->routeDataGenerator = new RevisionedRouteDataGenerator();
        parent::__construct(new Std(), $this->routeDataGenerator);
    }

    public function getRevision() : int
    {
        return $this->routeDataGenerator->getRevision();
    }

    public function getGroupPrefix() : string
    {
        return $this->currentGroupPrefix;
    }

    public function withGroupPrefix(string $prefix, callable $register) : void
    {
        $previous = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $prefix;

        try {
            $register();
        } finally {
            $this->currentGroupPrefix = $previous;
        }
    }
}
