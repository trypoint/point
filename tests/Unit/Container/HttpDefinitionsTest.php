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
use ReflectionClass;
use RuntimeException;

/**
 * @internal
 * @small
 */
final class HttpDefinitionsTest extends TestCase
{
    public function testContainsResponseFactory() : void
    {
        $definitions = new HttpDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertArrayHasKey(\Psr\Http\Message\ResponseFactoryInterface::class, $defs);
    }

    public function testContainsServerRequestFactory() : void
    {
        $definitions = new HttpDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertArrayHasKey(\Psr\Http\Message\ServerRequestFactoryInterface::class, $defs);
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
        $property->setAccessible(true);
        $property->setValue($definitions, static fn($class) => false);

        $definitions->getDefinitions();
    }
}
