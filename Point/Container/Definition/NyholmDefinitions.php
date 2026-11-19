<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container\Definition;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Point\Interfaces\DefinitionsInterface;
use Point\Interfaces\ServerRequestCreatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class NyholmDefinitions implements DefinitionsInterface
{
    public function getDefinitions() : array
    {
        $psr17Factory = new Psr17Factory();
        $serverRequestCreator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        return [
            ServerRequestFactoryInterface::class => $psr17Factory,
            ServerRequestCreatorInterface::class => new class ($serverRequestCreator) implements ServerRequestCreatorInterface {
                private ServerRequestCreator $serverRequestCreator;

                public function __construct(ServerRequestCreator $serverRequestCreator)
                {
                    $this->serverRequestCreator = $serverRequestCreator;
                }

                public function createServerRequestFromGlobals() : ServerRequestInterface
                {
                    return $this->serverRequestCreator->fromGlobals();
                }
            },
            ResponseFactoryInterface::class => $psr17Factory,
            StreamFactoryInterface::class => $psr17Factory,
            UriFactoryInterface::class => $psr17Factory,
            UploadedFileFactoryInterface::class => $psr17Factory,
        ];
    }
}
