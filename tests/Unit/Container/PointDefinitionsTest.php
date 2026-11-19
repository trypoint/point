<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Container;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Point\Container\Definition\PointDefinitions;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 * @small
 */
final class PointDefinitionsTest extends TestCase
{
    public function testContainsAppDefinition() : void
    {
        $definitions = new PointDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertArrayHasKey(\Point\App::class, $defs);
    }

    public function testContainsEmitterDefinition() : void
    {
        $definitions = new PointDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertArrayHasKey(\Point\Interfaces\EmitterInterface::class, $defs);
    }

    public function testContainsLoggerDefinition() : void
    {
        $definitions = new PointDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertArrayHasKey(LoggerInterface::class, $defs);
    }

    public function testContainsRouterDefinition() : void
    {
        $definitions = new PointDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertArrayHasKey(\Point\Interfaces\RouterInterface::class, $defs);
    }

    public function testGetDefinitionsReturnsArray() : void
    {
        $definitions = new PointDefinitions();
        $defs = $definitions->getDefinitions();

        self::assertIsArray($defs);
        self::assertNotEmpty($defs);
    }

    public function testLoggerDefinitionReturnsNullLogger() : void
    {
        $definitions = new PointDefinitions();
        $defs = $definitions->getDefinitions();

        $logger = $defs[LoggerInterface::class](new ArrayObject());
        self::assertInstanceOf(NullLogger::class, $logger);
    }
}
