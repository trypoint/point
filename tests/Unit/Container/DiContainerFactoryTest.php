<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Container;

use PHPUnit\Framework\TestCase;
use Point\Container\DiContainerFactory;
use Point\Container\SimpleContainer;
use Point\Interfaces\ContainerFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @small
 */
final class DiContainerFactoryTest extends TestCase
{
    public function testCreateContainerCreatesSimpleContainer() : void
    {
        $factory = new DiContainerFactory();
        $container = $factory->createContainer();

        self::assertInstanceOf(SimpleContainer::class, $container);
    }

    public function testCreateContainerIncludesHttpDefinitions() : void
    {
        $factory = new DiContainerFactory();
        $container = $factory->createContainer();

        self::assertTrue($container->has(\Psr\Http\Message\ResponseFactoryInterface::class));
        self::assertTrue($container->has(\Psr\Http\Message\ServerRequestFactoryInterface::class));
    }

    public function testCreateContainerIncludesPointDefinitions() : void
    {
        $factory = new DiContainerFactory();
        $container = $factory->createContainer();

        self::assertTrue($container->has(\Point\App::class));
        self::assertTrue($container->has(\Point\Interfaces\RouterInterface::class));
        self::assertTrue($container->has(\Point\Interfaces\EmitterInterface::class));
    }

    public function testCreateContainerReturnsContainerInterface() : void
    {
        $factory = new DiContainerFactory();
        $container = $factory->createContainer();

        self::assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testCreateContainerWithCustomDefinitions() : void
    {
        $factory = new DiContainerFactory();
        $container = $factory->createContainer([
            'custom' => static fn($c) => 'custom-value',
        ]);

        self::assertSame('custom-value', $container->get('custom'));
    }

    public function testImplementsContainerFactoryInterface() : void
    {
        $factory = new DiContainerFactory();
        self::assertInstanceOf(ContainerFactoryInterface::class, $factory);
    }
}
