<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\ValueObjects\Replacements;

/**
 * A value object representing a string replacement.
 */
class Replacement
{
    /**
     * Create a new Replacement instance.
     *
     * @param  array<int, string>  $search
     * @param  array<int, string>  $replace
     */
    public function __construct(
        public string|array $search,
        public string|array $replace,
    ) {}
}
