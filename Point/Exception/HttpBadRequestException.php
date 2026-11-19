<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpBadRequestException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 400;

    protected string $description = 'The server cannot or will not process ' .
        'the request due to an apparent client error.';

    /**
     * @var string
     */
    protected $message = 'Bad request.';

    protected string $title = '400 Bad Request';
}
