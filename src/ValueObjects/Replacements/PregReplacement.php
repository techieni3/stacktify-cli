<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\ValueObjects\Replacements;

/**
 * A value object representing a regex replacement.
 */
class PregReplacement
{
    /**
     * Create a new PregReplacement instance.
     */
    public function __construct(public string $regex, public string $replace) {}
}
