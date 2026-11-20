<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Middleware;

use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class HtmlExceptionMiddleware implements MiddlewareInterface
{
    use ExceptionMiddlewareTrait;

    private const DEFAULT_TYPE = 'text/html';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            $contentType = $this->detectMediaType($request);
            if ($contentType === null) {
                $preference = $this->mediaPreference($request->getHeaderLine('Accept'), self::DEFAULT_TYPE);
                if ($preference !== null && $preference[0] === 0.0) {
                    return $this->createResponse($exception, '', null);
                }

                $contentType = self::DEFAULT_TYPE;
            }

            return $this->createResponse($exception, $this->createPayload($exception), $contentType);
        }
    }

    private function createPayload(Throwable $exception) : string
    {
        if ($this->displayErrorDetails) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->renderExceptionFragment($exception);
        } else {
            $html = sprintf(
                '<p>%s</p>',
                htmlspecialchars($this->getErrorDescription($exception), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false),
            );
        }

        return $this->renderHtmlBody($this->getErrorTitle($exception), $html);
    }

    private function escapeHtml(?string $input = null) : string
    {
        return htmlspecialchars($input ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function renderExceptionFragment(Throwable $exception) : string
    {
        $html = sprintf(
            '<div><strong>Type:</strong> %s</div>',
            $this->escapeHtml($exception::class),
        );

        $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();
        $html .= sprintf('<div><strong>Code:</strong> %s</div>', $this->escapeHtml((string)$code));

        $html .= sprintf(
            '<div><strong>Message:</strong> %s</div>',
            $this->escapeHtml($exception->getMessage()),
        );

        $html .= sprintf(
            '<div><strong>File:</strong> %s</div>',
            $this->escapeHtml($exception->getFile()),
        );

        $html .= sprintf(
            '<div><strong>Line:</strong> %s</div>',
            $this->escapeHtml((string)$exception->getLine()),
        );

        $html .= '<h2>Trace</h2>';
        $html .= sprintf('<pre>%s</pre>', $this->escapeHtml($exception->getTraceAsString()));

        return $html;
    }

    private function renderHtmlBody(string $title = '', string $html = '') : string
    {
        return sprintf(
            '<!doctype html>'
            . '<html lang="en">'
            . '    <head>'
            . '        <meta charset="utf-8">'
            . '        <meta name="viewport" content="width=device-width, initial-scale=1">'
            . '        <title>%s</title>'
            . '        <style>'
            . '            body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif}'
            . '            h1{margin:0;font-size:48px;font-weight:normal;line-height:48px}'
            . '            strong{display:inline-block;width:65px}'
            . '            a{color:#007BFF;text-decoration:none}'
            . '            a:hover{text-decoration:underline}'
            . '        </style>'
            . '    </head>'
            . '    <body>'
            . '        <h1>%s</h1>'
            . '        <div>%s</div>'
            . '        <a href="#" onclick="window.history.go(-1); return false;">Go Back</a>'
            . '    </body>'
            . '</html>',
            $this->escapeHtml($title),
            $this->escapeHtml($title),
            $html,
        );
    }
}
