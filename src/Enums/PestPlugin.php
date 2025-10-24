<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

/**
 * Optional Pest plugins that can be scaffolded.
 */
enum PestPlugin: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

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
            self::Profanity->value,
            self::TypeCoverage->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::BrowserTest => 'Browser Test',
            self::Profanity => 'Profanity',
            self::TypeCoverage => 'Type Coverage',
            self::StressTesting => 'Stress Testing',
        };
    }

    public function package(): string
    {
        return match ($this) {
            self::BrowserTest => 'pestphp/pest-plugin-browser',
            self::Profanity => 'pestphp/pest-plugin-profanity',
            self::TypeCoverage => 'pestphp/pest-plugin-type-coverage',
            self::StressTesting => 'pestphp/pest-plugin-stressless',
        };
    }
}
