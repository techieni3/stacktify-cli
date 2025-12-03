<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Contracts;

use Techieni3\StacktifyCli\Services\Installers\InstallerContext;
use Techieni3\StacktifyCli\ValueObjects\Script;

/**
 * Defines the contract for an installable package.
 */
interface Installable
{
    /**
     * Get the Composer package name(s) to install.
     *
     * @return array<string>
     */
    public function dependencies(): array;

    /**
     * Get the development Composer package name(s) to install.
     *
     * @return array<string>
     */
    public function devDependencies(): array;

    /**
     * Get the NPM package name(s) to install.
     *
     * @return array<string>
     */
    public function npmDependencies(): array;

    /**
     * Get the development NPM package name(s) to install.
     *
     * @return array<string>
     */
    public function npmDevDependencies(): array;

    /**
     * Get the environment variables to add to .env.
     *
     * @return array<string, string>
     */
    public function environmentVariables(): array;

    /**
     * Get the stub files to publish (source => destination).
     *
     * @return array<string, string>
     */
    public function stubs(): array;

    /**
     * Get composer scripts to add to composer.json.
     *
     * @return array<Script>
     */
    public function composerScripts(): array;

    /**
     * Get composer scripts to add to composer.json 'post-update-cmd' section.
     *
     * @return array<string, string|array<string>>
     */
    public function composerPostUpdateScripts(): array;

    /**
     * Get npm scripts to add to package.json.
     *
     * @return array<Script>
     */
    public function npmScripts(): array;

    /**
     * Get the name of a config file.
     */
    public function configFile(): string;

    /**
     * Get configs to update.
     *
     * @return array<string, string|bool|array|callable>
     */
    public function configs(): array;

    /**
     * Commands / Scripts to run after package install.
     *
     * @return array<string>
     */
    public function postInstall(): array;

    /**
     * Get service provider configuration to add to AppServiceProvider.
     *
     * @return array{
     *     useStatements: array<string>,
     *     register: array<string>,
     *     boot: array<string>,
     *     newMethods: array<string>
     * }
     */
    public function serviceProviderConfig(): array;

    /**
     * Perform custom installation steps that cannot be represented declaratively.
     * This method is called after all standard installation steps are completed.
     */
    public function customInstall(InstallerContext $context): void;
}
