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

    private function createResponse(Throwable $exception, string $payload, ?string $contentType) : ResponseInterface
    {
        $statusCode = 500;

        if ($exception instanceof HttpException) {
            $code = $exception->getCode();
            if ($code >= 100 && $code <= 599) {
                $statusCode = $code;
            }
        }

        $response = $this->responseFactory->createResponse($statusCode);
        if ($contentType !== null) {
            $response = $response->withHeader('Content-Type', $contentType);
            $response->getBody()->write($payload);
        }

        if ($exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = $exception->getAllowedMethods();
            if (in_array('GET', $allowedMethods, true) && ! in_array('HEAD', $allowedMethods, true)) {
                $allowedMethods[] = 'HEAD';
            }
            $response = $response->withHeader('Allow', implode(', ', $allowedMethods));
        }

        return $response;
    }

    private function detectMediaType(ServerRequestInterface $request, ?string $alternative = null) : ?string
    {
        $accept = $request->getHeaderLine('Accept');

        if ($accept === '') {
            return self::DEFAULT_TYPE;
        }

        $best = $alternative === null ? null : $this->mediaPreference($accept, $alternative);
        $selected = null;
        $catchAll = isset($this->mimeTypes['*/*']);
        $types = $catchAll ? [self::DEFAULT_TYPE] : array_keys($this->mimeTypes);
        foreach ($types as $type) {
            if (! is_string($type)) {
                continue;
            }
            $preference = $this->mediaPreference($accept, $type);
            if ($catchAll && $preference === null && ($best[0] ?? 0.0) === 0.0) {
                // An explicit catch-all can render unmatched ranges, but cannot override an exclusion.
                $preference = $this->mediaPreference($accept, $type, allowUnmatched: true);
            }
            if ($preference !== null && $preference[0] > 0 && ($best === null || $preference > $best)) {
                $best = $preference;
                $selected = $type;
            }
        }

        return $selected;
    }

    /**
     * @return array{float, int, int}|null Quality, specificity, and inverse header position.
     */
    private function mediaPreference(string $accept, string $type, bool $allowUnmatched = false) : ?array
    {
        $wildcard = substr($type, 0, (int)strpos($type, '/')) . '/*';
        $best = null;
        foreach (explode(',', $accept) as $position => $range) {
            $parameters = explode(';', $range);
            $rangeType = strtolower(trim(array_shift($parameters)));
            $specificity = match ($rangeType) {
                $type => 2,
                $wildcard => 1,
                '*/*' => 0,
                default => $allowUnmatched && str_contains($rangeType, '/') ? 0 : -1,
            };
            if ($specificity < 0 || ($best !== null && $specificity < $best[1])) {
                continue;
            }

            $quality = 1.0;
            foreach ($parameters as $parameter) {
                if (preg_match('/^\s*q\s*=\s*(.*?)\s*$/iD', $parameter, $matches) === 1) {
                    $quality = preg_match('/^(?:0(?:\.[0-9]{0,3})?|1(?:\.0{0,3})?)$/D', $matches[1]) === 1
                        ? (float)$matches[1]
                        : 0.0;
                    break;
                }
            }

            if ($best === null || $specificity > $best[1] || $quality > $best[0]) {
                $best = [$quality, $specificity, -$position];
            }
        }

        return $best;
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
