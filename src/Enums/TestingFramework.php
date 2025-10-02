<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

enum TestingFramework: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

    case PhpUnit = 'phpunit';
    case Pest = 'pest';

    public static function default(): ?string
    {
        return self::Pest->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::PhpUnit => 'PHPUnit',
            self::Pest => 'Pest',
        };
    }
}
