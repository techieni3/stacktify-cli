<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

enum Authentication: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

    case None = 'none';
    case Laravel = 'laravel';
    case Socialite = 'socialite';

    public static function default(): string
    {
        return self::None->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::None => 'No authentication scaffolding',
            self::Laravel => "Laravel's built-in authentication",
            self::Socialite => "Laravel's built-in authentication + Socialite",
        };
    }
}
