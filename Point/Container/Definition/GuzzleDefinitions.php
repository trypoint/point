<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Container\Definition;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Point\Interfaces\DefinitionsInterface;
use Point\Interfaces\ServerRequestCreatorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class GuzzleDefinitions implements DefinitionsInterface
{
    public function getDefinitions() : array
    {
        $httpFactory = new HttpFactory();

        return [
            ServerRequestFactoryInterface::class => $httpFactory,
            ServerRequestCreatorInterface::class => new class () implements ServerRequestCreatorInterface {
                public function createServerRequestFromGlobals() : ServerRequestInterface
                {
                    return ServerRequest::fromGlobals();
                }
            },
            ResponseFactoryInterface::class => $httpFactory,
            StreamFactoryInterface::class => $httpFactory,
            UriFactoryInterface::class => $httpFactory,
            UploadedFileFactoryInterface::class => $httpFactory,
        ];
    }
}
