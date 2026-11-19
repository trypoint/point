<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

final class HttpMethodNotAllowedException extends HttpSpecializedException
{
    /**
     * @var string[]
     */
    protected array $allowedMethods = [];

    /**
     * @var int
     */
    protected $code = 405;

    protected string $description = 'The request method is not supported for the requested resource.';

    /**
     * @var string
     */
    protected $message = 'Method not allowed.';

    protected string $title = '405 Method Not Allowed';

    /**
     * @return string[]
     */
    public function getAllowedMethods() : array
    {
        return $this->allowedMethods;
    }

    /**
     * @param string[] $methods
     */
    public function setAllowedMethods(array $methods) : self
    {
        $this->allowedMethods = $methods;
        $this->message = 'Method not allowed.';

        return $this;
    }
}
