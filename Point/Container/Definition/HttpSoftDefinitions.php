<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container\Definition;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use HttpSoft\Message\UriFactory;
use HttpSoft\ServerRequest\ServerRequestCreator;
use Point\Interfaces\DefinitionsInterface;
use Point\Interfaces\ServerRequestCreatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class HttpSoftDefinitions implements DefinitionsInterface
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
            ServerRequestCreatorInterface::class => new class () implements ServerRequestCreatorInterface {
                public function createServerRequestFromGlobals() : ServerRequestInterface
                {
                    return ServerRequestCreator::createFromGlobals();
                }
            },
            ResponseFactoryInterface::class => $responseFactory,
            StreamFactoryInterface::class => $streamFactory,
            UriFactoryInterface::class => $uriFactory,
            UploadedFileFactoryInterface::class => $uploadedFileFactory,
        ];
    }
}
