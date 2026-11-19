<?php

/**
 * Point Framework (https://point.madalinmariusstan.dev).
 *
 * @license https://github.com/trypoint/point/blob/main/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Point\Interfaces;

/**
 * Interface for providing DI container service definitions.
 */
interface DefinitionsInterface
{
    /**
     * Return service definitions.
     *
     * @return array<string, mixed>
     */
    public function getDefinitions() : array;
}
