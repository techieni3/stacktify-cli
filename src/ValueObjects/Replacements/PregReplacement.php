<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\ValueObjects\Replacements;

class PregReplacement
{
    public function __construct(
        public string $regex,
        public string $replace,
    ) {}
}
