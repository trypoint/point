<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpTooManyRequestsException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 429;

    protected string $description = 'The client application has surpassed its rate limit, ' .
        'or number of requests they can send in a given period of time.';

    /**
     * @var string
     */
    protected $message = 'Too many requests.';

    protected string $title = '429 Too Many Requests';
}
