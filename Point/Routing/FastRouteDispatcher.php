<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use Point\Interfaces\DispatcherInterface;
use Point\Interfaces\RouterInterface;

final class FastRouteDispatcher implements DispatcherInterface
{
    private ?GroupCountBased $dispatcher = null;

    private RouterInterface $router;

    private ?RouteCollector $compiledCollector = null;

    private ?int $compiledRevision = null;

    /** @var array<mixed>|null */
    private ?array $compiledData = null;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function dispatch(string $httpMethod, string $uri) : array
    {
        // An encoded slash must not create a new path segment during decoding.
        if (stripos($uri, '%2f') !== false) {
            return [self::NOT_FOUND];
        }

        return $this->getDispatcher()->dispatch($httpMethod, rawurldecode($uri));
    }

    private function getDispatcher() : GroupCountBased
    {
        $collector = $this->router->getRouteCollector();
        $revision = $collector instanceof RevisionedRouteCollector ? $collector->getRevision() : null;
        $data = null;
        if ($revision === null) {
            // Custom collectors expose no revision, so compare their current route data.
            $data = $collector->getData();
        }

        if ($this->dispatcher !== null && $collector === $this->compiledCollector) {
            if ($revision === $this->compiledRevision && $data === $this->compiledData) {
                return $this->dispatcher;
            }
        }

        $this->dispatcher = new GroupCountBased($data ?? $collector->getData());
        $this->compiledCollector = $collector;
        $this->compiledRevision = $revision;
        $this->compiledData = $data;

        return $this->dispatcher;
    }
}
