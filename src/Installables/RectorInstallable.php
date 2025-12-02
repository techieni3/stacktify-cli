<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Installables;

use Override;
use Techieni3\StacktifyCli\ValueObjects\Script;

/**
 * An installable for Rector.
 *
 * @see https://getrector.com/documentation/
 * @see https://github.com/driftingly/rector-laravel
 */
final readonly class RectorInstallable extends AbstractInstallable
{
    /**
     * @return array<string>
     */
    #[Override]
    public function devDependencies(): array
    {
        return [
            'rector/rector',
            'driftingly/rector-laravel',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function stubs(): array
    {
        return [
            __DIR__.'/../../stubs/Rector/rector.stub' => 'rector.php',
        ];
    }

    /**
     * @return array<Script>
     */
    #[Override]
    public function composerScripts(): array
    {
        return [
            new Script(name: 'refactor', command: 'rector'),
            new Script(name: 'test:refactor', command: 'rector --dry-run'),
            new Script(name: 'format', command: [
                'rector',
                'pint --parallel',
            ]),
        ];
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function postInstall(): array
    {
        return [
            'composer run refactor',
        ];
    }
}
