<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Middleware;

use Psr\Http\Message\ResponseInterface;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function in_array;
use function is_array;

use function strtoupper;

final class MethodOverrideMiddleware implements MiddlewareInterface
{
    private const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return $handler->handle($request);
        }

        $methodHeader = strtoupper($request->getHeaderLine('X-Http-Method-Override'));

        if ($methodHeader && in_array($methodHeader, self::ALLOWED_METHODS, true)) {
            $request = $request->withMethod($methodHeader);
        } else {
            $body = $request->getParsedBody();

            if (is_array($body)) {
                $override = null;
                if (isset($body['_method']) && is_string($body['_method'])) {
                    $override = strtoupper($body['_method']);
                } elseif (isset($body['_METHOD']) && is_string($body['_METHOD'])) {
                    $override = strtoupper($body['_METHOD']);
                }

                if ($override !== null && in_array($override, self::ALLOWED_METHODS, true)) {
                    $request = $request->withMethod($override);
                }
            }

            if ($request->getBody()->eof()) {
                $request->getBody()->rewind();
            }
        }

        return $handler->handle($request);
    }
}
