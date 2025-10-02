<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

enum Frontend: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

    case Blade = 'blade';
    case Api = 'api';
    case Vue = 'vue';
    case React = 'react';
    case Livewire = 'livewire';
    case Filament = 'filament';

    public static function default(): ?string
    {
        return self::Blade->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Blade => 'Blade',
            self::Api => 'API Only',
            self::Vue => 'Vue',
            self::React => 'React',
            self::Livewire => 'Livewire',
            self::Filament => 'Filament',
        };
    }
}
