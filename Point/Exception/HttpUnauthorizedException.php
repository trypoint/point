<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpUnauthorizedException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 401;

    protected string $description = 'The request requires valid user authentication.';

    /**
     * @var string
     */
    protected $message = 'Unauthorized.';

    protected string $title = '401 Unauthorized';
}
