<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Installables;

use Override;
use Techieni3\StacktifyCli\ValueObjects\Script;

/**
 * An installable for Pint.
 */
final readonly class PintInstallable extends AbstractInstallable
{
    /**
     * @return array<string, string>
     */
    #[Override]
    public function stubs(): array
    {
        return [
            __DIR__.'/../../stubs/Pint/pint.stub' => 'pint.json',
        ];
    }

    /**
     * @return array<Script>
     */
    #[Override]
    public function composerScripts(): array
    {
        return [
            new Script(name: 'lint', command: 'pint'),
            new Script(name: 'test:lint', command: 'pint --test'),
        ];
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function postInstall(): array
    {
        return [
            'composer run lint',
        ];
    }
}
