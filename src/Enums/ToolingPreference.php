<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

/**
 * Preferences for applying additional tooling during scaffolding.
 */
enum ToolingPreference: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

    case Skip = 'skip';
    case Recommended = 'recommended';
    case Custom = 'custom';

    public static function default(): string
    {
        return self::Recommended->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Skip => 'No - start the installation now',
            self::Recommended => 'Yes - use the recommended settings and tools',
            self::Custom => 'I will choose the tools myself',
        };
    }
}
