<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

interface ServerRequestCreatorInterface
{
    public function createServerRequestFromGlobals() : ServerRequestInterface;
}
