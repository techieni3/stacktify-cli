<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\ValueObjects\Replacements;

class Replacement
{
    public function __construct(
        public string|array $search,
        public string|array $replace,
    ) {}
}
