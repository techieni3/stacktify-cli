<?php

namespace Techieni3\StacktifyCli\Installables;

use Techieni3\StacktifyCli\Contracts\Installable;

final readonly class PintInstallable implements Installable
{

    public function dependencies(): array
    {
        return [];
    }

    public function devDependencies(): array
    {
        return [];
    }

    public function environmentVariables(): array
    {
        return [];
    }

    public function stubs(): array
    {
        return [
            __DIR__.'/../../stubs/Pint/pint.stub' => 'pint.json',
        ];
    }

    public function composerScripts(): array
    {
        return [
            'lint' => 'pint',
            'test:lint' => 'pint --test'
        ];
    }

    public function postInstall(): array
    {
        return [];
    }

    public function postUpdate(): array
    {
        return [];
    }
}
