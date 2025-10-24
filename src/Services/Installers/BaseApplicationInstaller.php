<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

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
        //        $this->updateEnvironmentFiles();
        //        $this->updateApplicationConfig();
        //        $this->updateAppServiceProvider();
    }
}
