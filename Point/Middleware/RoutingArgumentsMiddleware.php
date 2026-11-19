<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Middleware;

use Point\Routing\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Add routing arguments to the request attributes.
 */
final class RoutingArgumentsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        /* @var RouteMatch|null $routeMatch */
        $routeMatch = $request->getAttribute(RouteMatch::class);

        if ($routeMatch instanceof RouteMatch) {
            foreach ($routeMatch->getArguments() as $key => $value) {
                $request = $request->withAttribute($key, $value);
            }
        }

        return $handler->handle($request);
    }
}
