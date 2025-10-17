<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\Installable;
use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Installables\OctaneInstallable;
use Techieni3\StacktifyCli\Installables\PintInstallable;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

/**
 * Optional developer tooling that can be scaffolded.
 */
enum DeveloperTool: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

    case Octane = 'octane';
    case Telescope = 'telescope';
    case Horizon = 'horizon';
    case Scout = 'scout';

    case Pint = 'pint';

    public static function default(): array
    {
        return [
            self::Pint,
        ];
    }

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            if ($case->isHiddenFromPrompt($case)) {
                continue;
            }
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    public function label(): string
    {
        return match ($this) {
            self::Octane => 'Laravel Octane',
            self::Telescope => 'Laravel Telescope',
            self::Horizon => 'Laravel Horizon',
            self::Scout => 'Laravel Scout',
            self::Pint => 'Pint',
        };
    }

    public function installable(): Installable
    {
        return match ($this) {
            self::Pint => new PintInstallable(),
            self::Octane => new OctaneInstallable(),
        };
    }

    private function isHiddenFromPrompt(self $tool): bool
    {
        $hidden = [self::Pint];

        return in_array($tool, $hidden, true);
    }
}
