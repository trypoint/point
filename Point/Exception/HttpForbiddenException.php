<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpForbiddenException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 403;

    protected string $description = 'You are not permitted to perform the requested operation.';

    /**
     * @var string
     */
    protected $message = 'Forbidden.';

    protected string $title = '403 Forbidden';
}
