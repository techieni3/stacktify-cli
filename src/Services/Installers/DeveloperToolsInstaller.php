<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Techieni3\StacktifyCli\Enums\DeveloperTool;

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

        $isStacktifySelected = in_array(DeveloperTool::Stacktify, $tools, true);

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

            $this->installNpmPackages(
                dependencies: $installable->npmDependencies(),
                devDependencies: $installable->npmDevDependencies(),
            );

            $this->publishStubs($installable->stubs());

            if ($tool === DeveloperTool::Octane) {
                $this->runCommands($installable->postInstall($isStacktifySelected));
            } else {
                $this->runCommands($installable->postInstall());
            }

            $this->addComposerScripts($installable->composerScripts());
            $this->appendComposerScripts($installable->composerPostUpdateScripts());

            $this->addNpmScripts($installable->npmScripts());

            $this->addEnvironmentVariables($installable->environmentVariables());

            $this->addConfigs($installable->configFile(), $installable->configs());

            $this->configureServiceProvider($installable->serviceProviderConfig());

            if ($isStacktifySelected) {
                $this->runCommands([
                    'composer run refactor',
                    'composer run lint',
                ]);
            }

            $this->commitChanges("chore: install and configure {$tool->name}");

            $this->notifySuccess($tool->name.' installed successfully');
        }
    }
}
