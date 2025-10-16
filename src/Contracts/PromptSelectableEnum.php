<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Contracts;

/**
 * Contract for CLI-selectable enums used in interactive prompts.
 *
 * Intended for use with console prompt libraries that support single- and multi-select inputs.
 */
interface PromptSelectableEnum
{
    /**
     * Returns the selectable options for prompting.
     *
     * For multi-select prompts, values must be unique.
     *
     * @return array<string,string>|list<string>
     */
    public static function options(): array;

    /**
     * The default selected value to preselect in the prompt, or null for no default.
     *
     * This must correspond to one of the option "value" keys returned by options().
     *
     * @return string|list<static>|null
     */
    public static function default(): string|array|null;

    /**
     * Converts the user's selection back to enum case(s).
     *
     * Returns the corresponding enum case for single-select, or an array of cases for multi-select.
     *
     * @param  list<string>  $selection  Selected value(s) returned by the prompt.
     * @return array<static>
     */
    public static function fromSelection(array $selection): array;

    /**
     * Human-readable label for the current enum case.
     */
    public function label(): string;
}
