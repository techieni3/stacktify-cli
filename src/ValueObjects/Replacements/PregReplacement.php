<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\ValueObjects\Replacements;

use InvalidArgumentException;

/**
 * A value object representing a regex replacement.
 */
final readonly class PregReplacement
{
    /**
     * Create a new PregReplacement instance.
     *
     * @throws InvalidArgumentException If the regex pattern is invalid
     */
    public function __construct(public string $regex, public string $replace)
    {
        $this->validateRegexPattern($regex);
    }

    /**
     * Validate a regex pattern.
     *
     * @throws InvalidArgumentException If the pattern is invalid
     */
    private function validateRegexPattern(string $pattern): void
    {
        if ($pattern === '') {
            throw new InvalidArgumentException('Regex pattern cannot be empty');
        }

        $result = @preg_match($pattern, '');

        if ($result === false) {
            throw new InvalidArgumentException(
                "Invalid regex pattern '{$pattern}': ".preg_last_error_msg()
            );
        }
    }
}
