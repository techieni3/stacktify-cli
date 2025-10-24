<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Contracts;

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
     * @return array<string, string>
     */
    public function composerScripts(): array;

    /**
     * Post-installation hook for additional setup.
     */
    public function postInstall(): array;

    /**
     * Post-update hook for additional setup.
     */
    public function postUpdate(): array;
}
