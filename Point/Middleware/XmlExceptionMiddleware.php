<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Middleware;

use DOMDocument;
use DOMElement;
use ErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class XmlExceptionMiddleware implements MiddlewareInterface
{
    use ExceptionMiddlewareTrait;

    private const DEFAULT_TYPE = 'application/xml';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            $contentType = $this->detectMediaType($request);

            if ($contentType === null) {
                throw $exception;
            }

            return $this->createResponse($exception, $this->createPayload($exception), $contentType);
        }
    }

    private function createPayload(Throwable $exception) : string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $errorElement = $dom->createElement('error');
        $dom->appendChild($errorElement);

        $this->appendText($dom, $errorElement, 'message', $this->getErrorTitle($exception));

        // If error details should be displayed
        if ($this->displayErrorDetails) {
            do {
                $exceptionElement = $dom->createElement('exception');

                $this->appendText($dom, $exceptionElement, 'type', $exception::class);

                $code = $exception instanceof ErrorException ? $exception->getSeverity() : $exception->getCode();
                $this->appendText($dom, $exceptionElement, 'code', (string)$code);

                $this->appendText($dom, $exceptionElement, 'message', $exception->getMessage());

                $this->appendText($dom, $exceptionElement, 'file', $exception->getFile());

                $this->appendText($dom, $exceptionElement, 'line', (string)$exception->getLine());

                $errorElement->appendChild($exceptionElement);
            } while ($exception = $exception->getPrevious());
        }

        return (string)$dom->saveXML();
    }

    private function appendText(DOMDocument $dom, DOMElement $parent, string $name, string $value) : void
    {
        $element = $dom->createElement($name);
        $element->appendChild($dom->createTextNode($value));
        $parent->appendChild($element);
    }
}
