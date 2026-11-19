<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

namespace Point\Factory;

use Point\App;
use Point\Container\DiContainerFactory;
use Point\Interfaces\ContainerFactoryInterface;
use Psr\Container\ContainerInterface;

final class AppFactory
{
    /**
     * The container factory used to create Point's DI container.
     *
     * If null, DiContainerFactory will be used as a default.
     *
     * @var ContainerFactoryInterface|null
     */
    private static ?ContainerFactoryInterface $containerFactory = null;

    /**
     * Create a new Point application instance.
     *
     * @param array<string, mixed> $definitions Optional container definitions.
     *
     * @return App
     */
    public static function create(array $definitions = []) : App
    {
        $containerBuilder = self::$containerFactory ?? new DiContainerFactory();

        return $containerBuilder->createContainer($definitions)->get(App::class);
    }

    /**
     * Create an application using an existing container instance.
     *
     * @param ContainerInterface $container
     *
     * @return App
     */
    public static function createFromContainer(ContainerInterface $container) : App
    {
        return new App($container);
    }

    /**
     * Set a custom container factory to build the application container.
     *
     * @param ContainerFactoryInterface $containerFactory
     *
     * @return void
     */
    public static function setContainerFactory(ContainerFactoryInterface $containerFactory) : void
    {
        self::$containerFactory = $containerFactory;
    }
}
