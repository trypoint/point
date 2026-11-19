<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpNotFoundException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 404;

    protected string $description = 'The requested resource could not be found. Please verify the URI and try again.';

    /**
     * @var string
     */
    protected $message = 'Not found.';

    protected string $title = '404 Not Found';
}
