<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpNotImplementedException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 501;

    protected string $description = 'The server does not support the functionality required to fulfill the request.';

    /**
     * @var string
     */
    protected $message = 'Not implemented.';

    protected string $title = '501 Not Implemented';
}
