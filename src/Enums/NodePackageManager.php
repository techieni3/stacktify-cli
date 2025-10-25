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

    /**
     * Get the installation command.
     */
    public function installCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm install',
            self::Yarn => 'yarn install',
            self::Pnpm => 'pnpm install',
            self::Bun => 'bun install',
        };
    }

    /**
     * Get the installation command for dev dependencies.
     */
    public function addDevCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm i -D',
            self::Yarn => 'yarn add --dev',
            self::Pnpm => 'pnpm add --save-dev',
            self::Bun => 'bun add --dev',
        };
    }

    /**
     * Get the add/install command for production dependencies.
     */
    public function addCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm i',
            self::Yarn => 'yarn add',
            self::Pnpm => 'pnpm add',
            self::Bun => 'bun add',
        };
    }

    /**
     * Get the run command prefix.
     */
    public function runCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm run',
            self::Yarn => 'yarn',
            self::Pnpm => 'pnpm',
            self::Bun => 'bun run',
        };
    }

    /**
     * Get the build command.
     */
    public function buildCommand(): string
    {
        return $this->runCommand().' build';
    }

    /**
     * Get the npx equivalent command (for running packages).
     */
    public function executeCommand(): string
    {
        return match ($this) {
            self::Npm => 'npx',
            self::Yarn => 'yarn dlx',
            self::Pnpm => 'pnpm dlx',
            self::Bun => 'bunx',
        };
    }

    /**
     * Get the executable binary name.
     */
    public function executable(): string
    {
        return $this->value;
    }

    /**
     * Get the remove/uninstall command.
     */
    public function removeCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm uninstall',
            self::Yarn => 'yarn remove',
            self::Pnpm => 'pnpm remove',
            self::Bun => 'bun remove',
        };
    }

    /**
     * Get the update command.
     */
    public function updateCommand(): string
    {
        return match ($this) {
            self::Npm => 'npm update',
            self::Yarn => 'yarn upgrade',
            self::Pnpm => 'pnpm update',
            self::Bun => 'bun update',
        };
    }
}
