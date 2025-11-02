<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Techieni3\StacktifyCli\Installables\PhpstanInstallable;
use Techieni3\StacktifyCli\Installables\PintInstallable;
use Techieni3\StacktifyCli\Installables\RectorInstallable;

/**
 * Apply baseline tweaks to a freshly scaffolded Laravel application.
 */
final class BaseApplicationInstaller extends AbstractInstaller
{
    /**
     * The installable for Pint.
     */
    private PintInstallable $pintInstallable;

    /**
     * Run all baseline customisations.
     */
    public function install(): void
    {
        $this->pintInstallable = new PintInstallable();

        $this->configurePint();
        $this->configureRector();
        $this->configurePhpstan();
    }

    /**
     * Configure Pint for the project.
     */
    private function configurePint(): void
    {
        // publish pint config
        $this->publishStubs($this->pintInstallable->stubs());

        // add a composer script
        $this->addScripts($this->pintInstallable->composerScripts());

        // run pint for all files
        $this->runScripts($this->pintInstallable->runAfterInstall());

        // commit changes
        $this->commitChanges('Configure Pint for the project');

        $this->notifySuccess('Pint configured successfully');
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

        // run pint for all files
        $this->runScripts($this->pintInstallable->runAfterInstall());

        // commit changes
        $this->commitChanges('Configure Rector for the project');

        $this->notifySuccess('Rector configured successfully');
    }

    private function configurePhpstan(): void
    {
        $installable = new PhpstanInstallable();

        // install phpstan
        $this->composer()->installDevDependencies($installable->devDependencies());

        // publish phpstan config
        $this->publishStubs($installable->stubs());

        // commit changes
        $this->commitChanges('Configure Phpstan for the project');

        $this->notifySuccess('Phpstan configured successfully');
    }
}
