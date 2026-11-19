<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Strategy;

use Point\Interfaces\RequestHandlerInvocationStrategyInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Invoke a route callable that implements RequestHandlerInterface.
 */
final class RequestHandler implements RequestHandlerInvocationStrategyInterface
{
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments,
    ) : ResponseInterface {
        return $callable($request);
    }
}
