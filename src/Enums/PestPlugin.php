<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

enum PestPlugin: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

    case ArcTest = 'arc test';
    case BrowserTest = 'browser test';
    case Profanity = 'profanity';
    case TypeCoverage = 'type coverage';
    case StressTesting = 'stress testing';

    /**
     * @return array<int, string>
     */
    public static function default(): array
    {
        return [
            self::ArcTest->value,
            self::BrowserTest->value,
            self::Profanity->value,
            self::TypeCoverage->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::ArcTest => 'Arc Test',
            self::BrowserTest => 'Browser Test',
            self::Profanity => 'Profanity',
            self::TypeCoverage => 'Type Coverage',
            self::StressTesting => 'Stress Testing',
        };
    }
}
