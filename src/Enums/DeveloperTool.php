<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\Installable;
use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Installables\OctaneInstallable;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

/**
 * Optional developer tooling that can be scaffolded.
 */
enum DeveloperTool: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

    case Stacktify = 'stacktify';
    case Octane = 'octane';
    case Telescope = 'telescope';
    case Horizon = 'horizon';
    case Scout = 'scout';

    /**
     * @return array{}
     */
    public static function default(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public static function recommended(): array
    {
        return [
            self::Stacktify->value,
            self::Octane->value,
            self::Telescope->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Stacktify => 'Stacktify Recommended configs (AppServiceProvider + other config tweaks)',
            self::Octane => 'Laravel Octane',
            self::Telescope => 'Laravel Telescope',
            self::Horizon => 'Laravel Horizon',
            self::Scout => 'Laravel Scout',
        };
    }

    public function installable(): ?Installable
    {
        return match ($this) {
            self::Octane => new OctaneInstallable(),
            default => null,
        };
    }

    public function requiresSpecialHandling(): bool
    {
        return $this === self::Stacktify;
    }
}
