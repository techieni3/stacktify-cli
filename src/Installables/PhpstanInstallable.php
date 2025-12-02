<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Installables;

use Override;
use Techieni3\StacktifyCli\ValueObjects\Script;

/**
 * An installable for Pint.
 */
final readonly class PhpstanInstallable extends AbstractInstallable
{
    /**
     * @return array<string>
     */
    #[Override]
    public function devDependencies(): array
    {
        return [
            'larastan/larastan',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function stubs(): array
    {
        return [
            __DIR__.'/../../stubs/Phpstan/phpstan.stub' => 'phpstan.neon',
        ];
    }

    /**
     * @return array<Script>
     */
    #[Override]
    public function composerScripts(): array
    {
        return [
            new Script(name: 'analyse', command: 'phpstan analyse'),
        ];
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function postInstall(): array
    {
        return [
            'composer run analyse',
        ];
    }
}
