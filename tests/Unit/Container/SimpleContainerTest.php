<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Container;

use PHPUnit\Framework\TestCase;
use Point\Container\SimpleContainer;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

/**
 * @internal
 * @small
 */
final class SimpleContainerTest extends TestCase
{
    public function testCanRegisterAndRetrieveArray() : void
    {
        $container = new SimpleContainer();
        $array = ['key' => 'value'];
        $container->set('array', $array);

        self::assertTrue($container->has('array'));
        self::assertSame($array, $container->get('array'));
    }

    public function testCanRegisterAndRetrieveDirectValue() : void
    {
        $container = new SimpleContainer();
        $value = 'test-value';
        $container->set('config', $value);

        self::assertTrue($container->has('config'));
        self::assertSame($value, $container->get('config'));
    }

    public function testCanRegisterAndRetrieveFactory() : void
    {
        $container = new SimpleContainer();
        $container->set('service', static fn($c) => new stdClass());

        self::assertTrue($container->has('service'));
        $instance = $container->get('service');
        self::assertInstanceOf(stdClass::class, $instance);
    }

    public function testCanRegisterAndRetrieveObject() : void
    {
        $container = new SimpleContainer();
        $object = new stdClass();
        $container->set('object', $object);

        self::assertTrue($container->has('object'));
        self::assertSame($object, $container->get('object'));
    }

    public function testConstructorAcceptsDefinitions() : void
    {
        $definitions = [
            'service' => static fn($c) => 'test',
        ];
        $container = new SimpleContainer($definitions);

        self::assertTrue($container->has('service'));
        self::assertSame('test', $container->get('service'));
    }

    public function testFactoryReceivesContainer() : void
    {
        $container = new SimpleContainer();
        $container->set('dependency', 'dep-value');
        $container->set('service', static fn($c) => $c->get('dependency'));

        self::assertSame('dep-value', $container->get('service'));
    }

    public function testGetThrowsNotFoundExceptionForMissingService() : void
    {
        $container = new SimpleContainer();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('nonexistent');
    }

    public function testHasReturnsFalseForMissingService() : void
    {
        $container = new SimpleContainer();
        self::assertFalse($container->has('nonexistent'));
    }

    public function testHasReturnsTrueForCachedInstance() : void
    {
        $container = new SimpleContainer();
        $container->set('service', static fn($c) => new stdClass());
        $container->get('service');

        self::assertTrue($container->has('service'));
    }

    public function testImplementsContainerInterface() : void
    {
        $container = new SimpleContainer();
        self::assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testSetClearsCachedInstance() : void
    {
        $container = new SimpleContainer();
        $container->set('service', static fn($c) => new stdClass());
        $instance1 = $container->get('service');

        $container->set('service', static fn($c) => new stdClass());
        $instance2 = $container->get('service');

        self::assertNotSame($instance1, $instance2);
    }

    public function testSetOverwritesExistingDefinition() : void
    {
        $container = new SimpleContainer();
        $container->set('service', static fn($c) => 'first');
        $container->set('service', static fn($c) => 'second');

        self::assertSame('second', $container->get('service'));
    }

    public function testSingletonBehaviorForDirectValue() : void
    {
        $container = new SimpleContainer();
        $object = new stdClass();
        $container->set('service', $object);

        $instance1 = $container->get('service');
        $instance2 = $container->get('service');

        self::assertSame($instance1, $instance2);
    }

    public function testSingletonBehaviorForFactory() : void
    {
        $container = new SimpleContainer();
        $container->set('service', static fn($c) => new stdClass());

        $instance1 = $container->get('service');
        $instance2 = $container->get('service');

        self::assertSame($instance1, $instance2);
    }
}
