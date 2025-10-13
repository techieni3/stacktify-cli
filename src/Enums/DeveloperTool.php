<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

enum DeveloperTool: string
{
    use BuildsPromptOptions;

    case Octane = 'octane';
    case Telescope = 'telescope';
    case Horizon = 'horizon';
    case Scout = 'scout';

    public function label(): string
    {
        return match ($this) {
            self::Octane => 'Laravel Octane',
            self::Telescope => 'Laravel Telescope',
            self::Horizon => 'Laravel Horizon',
            self::Scout => 'Laravel Scout',
        };
    }
}
