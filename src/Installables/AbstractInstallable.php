<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Installables;

use Techieni3\StacktifyCli\Contracts\Installable;

abstract readonly class AbstractInstallable implements Installable
{
    /**
     * @return array{}
     */
    public function dependencies(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function devDependencies(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function environmentVariables(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function stubs(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function composerScripts(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function npmScripts(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function postInstall(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function postUpdate(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function runAfterInstall(): array
    {
        return [];
    }
}
