<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpInternalServerErrorException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 500;

    protected string $description = 'Unexpected condition encountered preventing server from fulfilling request.';

    /**
     * @var string
     */
    protected $message = 'Internal server error.';

    protected string $title = '500 Internal Server Error';
}
