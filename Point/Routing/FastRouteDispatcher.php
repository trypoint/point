<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Routing;

use FastRoute\Dispatcher\GroupCountBased;
use Point\Interfaces\DispatcherInterface;
use Point\Interfaces\RouterInterface;

final class FastRouteDispatcher implements DispatcherInterface
{
    private ?GroupCountBased $dispatcher = null;

    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function dispatch(string $httpMethod, string $uri) : array
    {
        return $this->getDispatcher()->dispatch($httpMethod, rawurldecode(($uri)));
    }

    private function getDispatcher() : GroupCountBased
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new GroupCountBased(
                $this->router->getRouteCollector()->getData()
            );
        }

        return $this->dispatcher;
    }
}
