<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Container;

use PHPUnit\Framework\TestCase;
use Point\Container\Definition\HttpDefinitions;
use Point\Factory\AppFactory;
use Point\Interfaces\ServerRequestCreatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use ReflectionClass;
use RuntimeException;

/**
 * @internal
 * @small
 */
final class HttpDefinitionsTest extends TestCase
{
    public function testBundledProviderCreatesRequestFromGlobals() : void
    {
        $definitions = $this->availableDefinitions($this->httpSoftClasses());
        self::assertInstanceOf(\HttpSoft\Message\ResponseFactory::class, $definitions[ResponseFactoryInterface::class]);

        $previous = $_SERVER;
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/globals', 'HTTP_HOST' => 'localhost'];
        try {
            $request = $definitions[ServerRequestCreatorInterface::class]->createServerRequestFromGlobals();
            self::assertSame('GET', $request->getMethod());
            self::assertSame('/globals', $request->getUri()->getPath());
        } finally {
            $_SERVER = $previous;
        }
    }

    public function testIncompleteNyholmFallsBackToHttpSoft() : void
    {
        $definitions = $this->availableDefinitions([
            \Nyholm\Psr7\Factory\Psr17Factory::class,
            ...$this->httpSoftClasses(),
        ]);

        self::assertInstanceOf(\HttpSoft\Message\ResponseFactory::class, $definitions[ResponseFactoryInterface::class]);
        self::assertInstanceOf(\HttpSoft\Message\ServerRequestFactory::class, $definitions[ServerRequestFactoryInterface::class]);
    }

    public function testCompleteNyholmKeepsPrecedence() : void
    {
        $definitions = $this->availableDefinitions([
            \Nyholm\Psr7\Factory\Psr17Factory::class,
            \Nyholm\Psr7Server\ServerRequestCreator::class,
            ...$this->httpSoftClasses(),
        ]);

        self::assertInstanceOf(\Nyholm\Psr7\Factory\Psr17Factory::class, $definitions[ResponseFactoryInterface::class]);
    }

    public function testExplicitFactoriesAndGlobalsCreatorStayInUse() : void
    {
        $requests = new \HttpSoft\Message\ServerRequestFactory();
        $responses = new \HttpSoft\Message\ResponseFactory();
        $creator = $this->createMock(ServerRequestCreatorInterface::class);
        $creator->expects(self::once())->method('createServerRequestFromGlobals')->willReturn($requests->createServerRequest('GET', '/globals'));
        $app = AppFactory::create([
            ServerRequestFactoryInterface::class => $requests,
            ResponseFactoryInterface::class => $responses,
            ServerRequestCreatorInterface::class => $creator,
        ]);
        $app->any('/{source}', static function ($request, $response, array $arguments) {
            $response->getBody()->write($arguments['source']);

            return $response;
        });

        self::assertSame($requests, $app->getContainer()->get(ServerRequestFactoryInterface::class));
        self::assertSame($responses, $app->getContainer()->get(ResponseFactoryInterface::class));
        $response = $app->handle((new \Nyholm\Psr7\Factory\Psr17Factory())->createServerRequest('GET', '/injected'));
        self::assertInstanceOf(\HttpSoft\Message\Response::class, $response);
        self::assertSame('injected', (string)$response->getBody());
        $this->expectOutputString('globals');
        $app->run();
    }

    public function testContainsResponseFactory() : void
    {
        $definitions = new HttpDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertArrayHasKey(ResponseFactoryInterface::class, $defs);
    }

    public function testContainsServerRequestFactory() : void
    {
        $definitions = new HttpDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertArrayHasKey(ServerRequestFactoryInterface::class, $defs);
    }

    public function testGetDefinitionsReturnsArray() : void
    {
        $definitions = new HttpDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertIsArray($defs);
        self::assertNotEmpty($defs);
    }

    public function testThrowsExceptionWhenNoImplementationFound() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not detect any PSR-17 ResponseFactory implementations');

        $definitions = new HttpDefinitions();
        // Use reflection to test with a mocked class_exists that returns false
        $reflection = new ReflectionClass($definitions);
        $property = $reflection->getProperty('classExists');
        $property->setValue($definitions, static fn($class) => false);

        $definitions->getDefinitions();
    }

    private function availableDefinitions(array $classes) : array
    {
        $definitions = new HttpDefinitions();
        $reflection = new ReflectionClass($definitions);
        $reflection->getProperty('classExists')->setValue($definitions, static fn(string $class) => in_array($class, $classes, true));

        return $definitions->getDefinitions();
    }

    private function httpSoftClasses() : array
    {
        return [
            \HttpSoft\Message\RequestFactory::class,
            \HttpSoft\Message\ResponseFactory::class,
            \HttpSoft\Message\ServerRequestFactory::class,
            \HttpSoft\Message\StreamFactory::class,
            \HttpSoft\Message\UriFactory::class,
            \HttpSoft\Message\UploadedFileFactory::class,
            \HttpSoft\ServerRequest\ServerRequestCreator::class,
        ];
    }
}
