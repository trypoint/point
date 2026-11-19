<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev)
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Exception;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    protected string $description = '';

    protected ServerRequestInterface $request;

    protected string $title = '';

    public function __construct(
        ServerRequestInterface $request,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function getRequest() : ServerRequestInterface
    {
        return $this->request;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setDescription(string $description) : self
    {
        $this->description = $description;

        return $this;
    }

    public function setTitle(string $title) : self
    {
        $this->title = $title;

        return $this;
    }
}
