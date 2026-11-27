<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Point\Factory\AppFactory;
use Point\Interfaces\ServerRequestCreatorInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 * @small
 */
final class ServerRequestCreatorTest extends TestCase
{
    public function testCanCreateFromGlobals() : void
    {
        $container = AppFactory::create()->getContainer();
        $creator = $container->get(ServerRequestCreatorInterface::class);

        $request = $creator->createServerRequestFromGlobals();
        self::assertInstanceOf(ServerRequestInterface::class, $request);
    }
}
