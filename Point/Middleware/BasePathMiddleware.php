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
            $this->router->setBasePath($basePath);
        }

        return $handler->handle($request);
    }

    /**
     * Return basePath for most common webservers.
     */
    private function detectBasePath(ServerRequestInterface $request) : string
    {
        $serverParams = $request->getServerParams();
        $scriptName = $serverParams['SCRIPT_NAME'] ??
            $serverParams['PHP_SELF'] ??
            $serverParams['ORIG_SCRIPT_NAME'] ?? '';
        $scriptName = str_replace('\\', '/', dirname($scriptName, 2));

        if ($scriptName === '/') {
            return '';
        }

        $path = $request->getUri()->getPath();
        $length = strlen($scriptName);
        $path = $length > 0 ? substr($path, 0, $length) : $path;

        return strlen($path) > 1 ? $path : '';
    }
}
