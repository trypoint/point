<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpGoneException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 410;

    protected string $description = 'The target resource is no longer available at the origin server.';

    /**
     * @var string
     */
    protected $message = 'Gone.';

    protected string $title = '410 Gone';
}
