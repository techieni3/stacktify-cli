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
    public function npmDependencies(): array
    {
        return [];
    }

    /**
     * @return array{}
     */
    public function npmDevDependencies(): array
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
    public function composerPostUpdateScripts(): array
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

    public function configFile(): string
    {
        return '';
    }

    /**
     * @return array{}
     */
    public function configs(): array
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
     * @return array{
     *     useStatements: array{},
     *     register: array{},
     *     boot: array{},
     *     newMethods: array{}
     * }
     */
    public function serviceProviderConfig(): array
    {
        return [
            'useStatements' => [],
            'register' => [],
            'boot' => [],
            'newMethods' => [],
        ];
    }
}
