<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Error;

use PHPUnit\Framework\TestCase;
use Point\Exception\HttpBadRequestException;
use Point\Exception\HttpException;
use Point\Exception\HttpForbiddenException;
use Point\Exception\HttpGoneException;
use Point\Exception\HttpInternalServerErrorException;
use Point\Exception\HttpMethodNotAllowedException;
use Point\Exception\HttpNotFoundException;
use Point\Exception\HttpNotImplementedException;
use Point\Exception\HttpTooManyRequestsException;
use Point\Exception\HttpUnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @internal
 * @small
 */
final class HttpExceptionTest extends TestCase
{
    public function testExceptionMessage() : void
    {
        $exception = new HttpNotFoundException($this->createRequest(), 'Custom message');
        self::assertEquals('Custom message', $exception->getMessage());
    }

    public function testHttpBadRequestExceptionHasCode400() : void
    {
        $exception = new HttpBadRequestException($this->createRequest());
        self::assertEquals(400, $exception->getCode());
    }

    public function testHttpExceptionImplementsThrowable() : void
    {
        $exception = new HttpException($this->createRequest(), 'Test', 400);
        self::assertInstanceOf(Throwable::class, $exception);
    }

    public function testHttpExceptionStoresStatusCode() : void
    {
        $exception = new HttpException($this->createRequest(), 'Test', 418);
        self::assertEquals(418, $exception->getCode());
    }

    public function testHttpForbiddenExceptionHasCode403() : void
    {
        $exception = new HttpForbiddenException($this->createRequest());
        self::assertEquals(403, $exception->getCode());
    }

    public function testHttpGoneExceptionHasCode410() : void
    {
        $exception = new HttpGoneException($this->createRequest());
        self::assertEquals(410, $exception->getCode());
    }

    public function testHttpInternalServerErrorExceptionHasCode500() : void
    {
        $exception = new HttpInternalServerErrorException($this->createRequest());
        self::assertEquals(500, $exception->getCode());
    }

    public function testHttpMethodNotAllowedExceptionHasCode405() : void
    {
        $exception = new HttpMethodNotAllowedException($this->createRequest());
        self::assertEquals(405, $exception->getCode());
    }

    public function testHttpMethodNotAllowedExceptionStoresAllowedMethods() : void
    {
        $exception = new HttpMethodNotAllowedException($this->createRequest());
        $exception->setAllowedMethods(['GET', 'POST', 'PUT']);
        self::assertEquals(['GET', 'POST', 'PUT'], $exception->getAllowedMethods());
    }

    public function testHttpNotFoundExceptionHasCode404() : void
    {
        $exception = new HttpNotFoundException($this->createRequest());
        self::assertEquals(404, $exception->getCode());
    }

    public function testHttpNotImplementedExceptionHasCode501() : void
    {
        $exception = new HttpNotImplementedException($this->createRequest());
        self::assertEquals(501, $exception->getCode());
    }

    public function testHttpTooManyRequestsExceptionHasCode429() : void
    {
        $exception = new HttpTooManyRequestsException($this->createRequest());
        self::assertEquals(429, $exception->getCode());
    }

    public function testHttpUnauthorizedExceptionHasCode401() : void
    {
        $exception = new HttpUnauthorizedException($this->createRequest());
        self::assertEquals(401, $exception->getCode());
    }

    private function createRequest() : ServerRequestInterface
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return $factory->createServerRequest('GET', '/');
    }
}
