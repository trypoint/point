<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container\Definition;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laminas\Diactoros\UriFactory;
use Point\Interfaces\DefinitionsInterface;
use Point\Interfaces\ServerRequestCreatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class LaminasDefinitions implements DefinitionsInterface
{
    public function getDefinitions() : array
    {
        $serverRequestFactory = new ServerRequestFactory();
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();
        $uriFactory = new UriFactory();
        $uploadedFileFactory = new UploadedFileFactory();

        return [
            ServerRequestFactoryInterface::class => $serverRequestFactory,
            ServerRequestCreatorInterface::class => new class ($serverRequestFactory) implements ServerRequestCreatorInterface {
                private ServerRequestFactory $serverRequestFactory;

                public function __construct(ServerRequestFactory $serverRequestFactory)
                {
                    $this->serverRequestFactory = $serverRequestFactory;
                }

                public function createServerRequestFromGlobals() : ServerRequestInterface
                {
                    return $this->serverRequestFactory->fromGlobals();
                }
            },
            ResponseFactoryInterface::class => $responseFactory,
            StreamFactoryInterface::class => $streamFactory,
            UriFactoryInterface::class => $uriFactory,
            UploadedFileFactoryInterface::class => $uploadedFileFactory,
        ];
    }
}
