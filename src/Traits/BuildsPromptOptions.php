<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Traits;

use LogicException;
use ReflectionEnum;

/**
 * Helper for building prompt options from enum cases and mapping selections back to cases.
 */
trait BuildsPromptOptions
{
    /**
     * Builds an associative array of selectable options for prompts.
     *
     * Format: [value => label], where "value" is the enum's string-backed value and "label" is provided by label().
     *
     * @return array<string, string>|array<int, string> Map of enum value to human-readable label.
     *
     * @throws LogicException If used on a non-backed or non-string-backed enum.
     */
    public static function options(): array
    {
        $ref = new ReflectionEnum(self::class);
        if ( ! $ref->isBacked()) {
            throw new LogicException(self::class.' must be a backed enum to build prompt options.');
        }

        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /**
     * Converts selected value(s) returned by a prompt back into enum case(s).
     *
     * For single-select, pass a string and receive the corresponding enum case.
     * For multi-select, pass an array of strings and receive an array of enum cases (in the same order).
     *
     * @param  string|array  $selection  Selected value or list of values.
     * @return self|array<self> Enum case for single-select or array of cases for multi-select.
     */
    public static function fromSelection(string|array $selection): self|array
    {
        if (is_array($selection)) {
            return array_map(static fn ($v) => self::from($v), $selection);
        }

        return self::from($selection);
    }
}
