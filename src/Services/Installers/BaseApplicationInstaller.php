<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Techieni3\StacktifyCli\Installables\PintInstallable;

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
    }
}
