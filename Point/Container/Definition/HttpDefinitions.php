<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container\Definition;

use GuzzleHttp\Psr7;
use HttpSoft\Message;
use HttpSoft\ServerRequest\ServerRequestCreator as HttpSoftRequestCreator;
use Laminas\Diactoros;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator as NyholmRequestCreator;
use Point\Interfaces\DefinitionsInterface;
use RuntimeException;

/**
 * Selects the appropriate PSR-7/PSR-17 implementation based on
 * which libraries are available at runtime.
 */
final class HttpDefinitions implements DefinitionsInterface
{
    /**
     * Providers in selection order, with the classes used by their definitions.
     *
     * @var array<class-string<DefinitionsInterface>, list<class-string>>
     */
    private array $classes = [
        NyholmDefinitions::class => [Psr17Factory::class, NyholmRequestCreator::class],
        LaminasDefinitions::class => [
            Diactoros\ResponseFactory::class,
            Diactoros\ServerRequestFactory::class,
            Diactoros\StreamFactory::class,
            Diactoros\UploadedFileFactory::class,
            Diactoros\UriFactory::class,
        ],
        GuzzleDefinitions::class => [Psr7\ServerRequest::class, Psr7\HttpFactory::class],
        HttpSoftDefinitions::class => [
            Message\ResponseFactory::class,
            Message\ServerRequestFactory::class,
            Message\StreamFactory::class,
            Message\UploadedFileFactory::class,
            Message\UriFactory::class,
            HttpSoftRequestCreator::class,
        ],
    ];

    /**
     * Callable used to check whether a class exists.
     *
     * @var callable(string): bool
     */
    private $classExists = 'class_exists';

    public function getDefinitions() : array
    {
        foreach ($this->classes as $definitionClass => $classes) {
            foreach ($classes as $class) {
                if (! ($this->classExists)($class)) {
                    continue 2;
                }
            }

            return (new $definitionClass())->getDefinitions();
        }

        throw new RuntimeException(
            'Could not detect any PSR-17 ResponseFactory implementations. '
            . 'Please install a supported implementation. '
            . 'See https://github.com/trypoint/point/blob/main/README.md for a list of supported implementations.',
        );
    }
}
