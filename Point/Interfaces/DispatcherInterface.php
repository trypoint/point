<?php

declare(strict_types=1);

namespace Point\Interfaces;

interface DispatcherInterface
{
    public const FOUND = 1;

    public const METHOD_NOT_ALLOWED = 2;

    public const NOT_FOUND = 0;

    /**
     * @return array<int, mixed>
     */
    public function dispatch(string $httpMethod, string $uri) : array;
}
