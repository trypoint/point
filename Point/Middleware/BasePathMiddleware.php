<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Middleware;

use Point\Interfaces\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class BasePathMiddleware implements MiddlewareInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $basePath = $this->router->getBasePath();
        if ($basePath === null) {
            $basePath = $this->detectBasePath($request);
            if ($basePath !== null) {
                $this->router->setBasePath($basePath);
            }
        }

        return $handler->handle($request);
    }

    private function detectBasePath(ServerRequestInterface $request) : ?string
    {
        $scriptName = $request->getServerParams()['SCRIPT_NAME'] ?? null;
        if (! is_string($scriptName) || ! str_starts_with($scriptName, '/')
            || str_starts_with($scriptName, '//') || str_ends_with($scriptName, '/')
            || str_contains($scriptName, '\\')) {
            return null;
        }

        return substr($scriptName, 0, (int)strrpos($scriptName, '/'));
    }
}
