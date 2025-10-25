<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

/**
 * Installs the selected developer tools.
 */
final class DeveloperToolsInstaller extends AbstractInstaller
{
    /**
     * Install selected developer tools.
     */
    public function install(): void
    {
        $tools = $this->config()->getDeveloperTools();

        if ($tools === []) {
            return;
        }

        foreach ($tools as $tool) {
            if ($tool->requiresSpecialHandling()) {
                continue;
            }

            $installable = $tool->installable();

            if ($installable === null) {
                continue;
            }

            $this->installPackages(
                dependencies: $installable->dependencies(),
                devDependencies: $installable->devDependencies(),
            );

            $this->publishStubs($installable->stubs());
        }
    }
}
