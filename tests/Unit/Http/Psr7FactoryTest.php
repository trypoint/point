<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Interfaces\ServerRequestCreatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @internal
 * @small
 */
final class Psr7FactoryTest extends TestCase
{
    public function testCanCreateResponse() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $factory = $container->get(ResponseFactoryInterface::class);

        $response = $factory->createResponse(201);
        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertEquals(201, $response->getStatusCode());
    }

    public function testCanCreateServerRequest() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $factory = $container->get(ServerRequestFactoryInterface::class);

        $request = $factory->createServerRequest('GET', '/test');
        self::assertInstanceOf(ServerRequestInterface::class, $request);
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('/test', $request->getUri()->getPath());
    }

    public function testCanCreateStream() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $factory = $container->get(StreamFactoryInterface::class);

        $stream = $factory->createStream('test content');
        self::assertEquals('test content', (string)$stream);
    }

    public function testCanCreateUploadedFile() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $factory = $container->get(UploadedFileFactoryInterface::class);

        $stream = $container->get(StreamFactoryInterface::class)->createStream('test');
        $file = $factory->createUploadedFile($stream);
        self::assertInstanceOf(\Psr\Http\Message\UploadedFileInterface::class, $file);
    }

    public function testCanCreateUri() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $factory = $container->get(UriFactoryInterface::class);

        $uri = $factory->createUri('https://example.com/path?query=value');
        self::assertEquals('https://example.com/path?query=value', (string)$uri);
    }

    public function testNyholmFactoriesAvailable() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        self::assertTrue($container->has(ResponseFactoryInterface::class));
        self::assertTrue($container->has(ServerRequestFactoryInterface::class));
        self::assertTrue($container->has(StreamFactoryInterface::class));
        self::assertTrue($container->has(UploadedFileFactoryInterface::class));
        self::assertTrue($container->has(UriFactoryInterface::class));
        self::assertTrue($container->has(ServerRequestCreatorInterface::class));
    }
}

/**
 * @internal
 * @small
 */
final class ServerRequestCreatorTest extends TestCase
{
    public function testCanCreateFromGlobals() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $creator = $container->get(ServerRequestCreatorInterface::class);

        $request = $creator->createServerRequestFromGlobals();
        self::assertInstanceOf(ServerRequestInterface::class, $request);
    }
}

/**
 * @internal
 * @small
 */
final class ResponseEmitterTest extends TestCase
{
    public function testEmitterExists() : void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        self::assertTrue($container->has(\Point\Interfaces\EmitterInterface::class));
        $emitter = $container->get(\Point\Interfaces\EmitterInterface::class);
        self::assertInstanceOf(\Point\Interfaces\EmitterInterface::class, $emitter);
    }
}
