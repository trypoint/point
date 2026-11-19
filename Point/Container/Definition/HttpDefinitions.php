<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container\Definition;

use GuzzleHttp\Psr7\ServerRequest;
use HttpSoft\Message\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Point\Interfaces\DefinitionsInterface;
use RuntimeException;

/**
 * Selects the appropriate PSR-7/PSR-17 implementation based on
 * which libraries are available at runtime.
 */
final class HttpDefinitions implements DefinitionsInterface
{
    /**
     * Mapping of PSR-17/PSR-7 factory classes to their DI definition providers.
     *
     * @var array<string, string>
     */
    private array $classes = [
        Psr17Factory::class => NyholmDefinitions::class,
        ResponseFactory::class => LaminasDefinitions::class,
        ServerRequest::class => GuzzleDefinitions::class,
        RequestFactory::class => HttpSoftDefinitions::class,
    ];

    /**
     * Callable used to check whether a class exists.
     *
     * @var callable(string): bool
     */
    private $classExists = 'class_exists';

    public function getDefinitions() : array
    {
        foreach ($this->classes as $factory => $definitionClass) {
            if (call_user_func($this->classExists, $factory)) {
                /** @var DefinitionsInterface $definitionClass */
                return (new $definitionClass())->getDefinitions();
            }
        }

        throw new RuntimeException(
            'Could not detect any PSR-17 ResponseFactory implementations. '
            . 'Please install a supported implementation. '
            . 'See https://github.com/trypoint/point/blob/main/README.md for a list of supported implementations.',
        );
    }
}
