<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Techieni3\StacktifyCli\Installables\PintInstallable;
use Techieni3\StacktifyCli\Installables\RectorInstallable;

/**
 * Apply baseline tweaks to a freshly scaffolded Laravel application.
 */
final class BaseApplicationInstaller extends AbstractInstaller
{
    /**
     * Run all baseline customisations.
     */
    public function install(): void
    {
        $this->configureRector();
        $this->configurePint();
    }

    /**
     * Configure Pint for the project.
     */
    private function configurePint(): void
    {
        $installable = new PintInstallable();

        // publish pint config
        $this->publishStubs($installable->stubs());

        // add a composer script
        $this->addScripts($installable->composerScripts());

        // run pint for all files
        $this->runScripts($installable->runAfterInstall());

        // commit changes
        $this->commitChanges('Configure Pint for the project');
    }

    /**
     * Configure Rector for the project.
     */
    private function configureRector(): void
    {
        $installable = new RectorInstallable();

        // install rector
        $this->composer()->installDevDependencies($installable->devDependencies());

        // publish pint config
        $this->publishStubs($installable->stubs());

        // add a composer script
        $this->addScripts($installable->composerScripts());

        // run rector for all files
        $this->runScripts($installable->runAfterInstall());

        // commit changes
        $this->commitChanges('Configure Rector for the project');
    }
}
