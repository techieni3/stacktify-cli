<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Installables;

use Techieni3\StacktifyCli\Contracts\Installable;

/**
 * An installable for Pint.
 */
final readonly class PintInstallable implements Installable
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

    public function stubs(): array
    {
        return [
            __DIR__.'/../../stubs/Pint/pint.stub' => 'pint.json',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function composerScripts(): array
    {
        return [
            'lint' => 'pint',
            'test:lint' => 'pint --test',
        ];
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
}
