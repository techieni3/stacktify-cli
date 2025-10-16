<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

/**
 * Supported Node package managers for frontend tooling.
 */
enum NodePackageManager: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;
    case Npm = 'npm';
    case Yarn = 'yarn';
    case Pnpm = 'pnpm';
    case Bun = 'bun';

    public static function default(): string
    {
        return self::Npm->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Npm => 'Npm',
            self::Yarn => 'Yarn',
            self::Pnpm => 'Pnpm',
            self::Bun => 'Bun',
        };
    }

    public function installCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm install',
            self::Yarn => 'yarn install',
            self::Pnpm => 'pnpm install',
            self::Bun => 'bun install',
        };
    }

    public function runCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm run',
            self::Yarn => 'yarn',
            self::Pnpm => 'pnpm',
            self::Bun => 'bun run',
        };
    }

    public function buildCommand(): string
    {
        return $this->runCommand().' build';
    }

    public function executable(): string
    {
        return $this->value;
    }
}
