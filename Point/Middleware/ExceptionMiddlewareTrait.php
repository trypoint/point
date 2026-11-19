<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Middleware;

use Point\Exception\HttpException;
use Point\Exception\HttpMethodNotAllowedException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

trait ExceptionMiddlewareTrait
{
    private string $defaultErrorDescription = 'A website error has occurred. Sorry for the temporary inconvenience.';

    private string $defaultErrorTitle = 'Application Error';

    private bool $displayErrorDetails = false;

    /**
     * @var array|int[]
     */
    private array $mimeTypes = [self::DEFAULT_TYPE => 1];

    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Parses the 'Accept' header to extract media types.
     * This method doesn't consider the quality values (q-values).
     *
     * @param string|null $accept The value of the 'Accept' header
     *
     * @return array<string> An array of normalized media types from the 'Accept' header
     */
    public function parseAcceptHeader(?string $accept) : array
    {
        $acceptTypes = $accept ? explode(',', $accept) : [];

        $cleanTypes = [];

        foreach ($acceptTypes as $type) {
            // Remove optional parameters like ";q=0.8"
            $name = strtolower(trim(explode(';', $type)[0]));
            $cleanTypes[] = $name;
        }

        return $cleanTypes;
    }

    public function withErrorDetails(bool $flag) : self
    {
        $clone = clone $this;
        $clone->displayErrorDetails = $flag;

        return $clone;
    }

    public function withMimeType(string $mimeType) : self
    {
        $clone = clone $this;
        $clone->mimeTypes[$mimeType] = 1;

        return $clone;
    }

    private function createResponse(Throwable $exception, string $payload, string $contentType) : ResponseInterface
    {
        $code = $exception->getCode();
        $statusCode = ($code >= 100 && $code <= 599) ? $code : 500;

        $response = $this->responseFactory
            ->createResponse($statusCode)
            ->withHeader('Content-Type', $contentType);

        $response->getBody()->write($payload);

        if ($exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = implode(', ', $exception->getAllowedMethods());
            $response = $response->withHeader('Allow', $allowedMethods);
        }

        return $response;
    }

    private function detectMediaType(ServerRequestInterface $request) : ?string
    {
        $accept = $request->getHeaderLine('Accept');

        // If no Accept header, default to DEFAULT_TYPE
        if ($accept === '') {
            return self::DEFAULT_TYPE;
        }

        // Performance optimized for most cases
        if ($accept === self::DEFAULT_TYPE || isset($this->mimeTypes['*/*'])) {
            return self::DEFAULT_TYPE;
        }

        // Parses complex Accept headers
        $mimeTypes = $this->parseAcceptHeader($accept);

        foreach ($mimeTypes as $type) {
            if (isset($this->mimeTypes[$type])) {
                return $type;
            }
        }

        return null;
    }

    private function getErrorDescription(Throwable $exception) : string
    {
        if ($exception instanceof HttpException) {
            return $exception->getDescription();
        }

        return $this->defaultErrorDescription;
    }

    private function getErrorTitle(Throwable $exception) : string
    {
        if ($exception instanceof HttpException) {
            return $exception->getTitle();
        }

        return $this->defaultErrorTitle;
    }
}
