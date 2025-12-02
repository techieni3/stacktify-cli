<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Installables;

use Closure;
use Override;

/**
 * An installable for Laravel Octane.
 */
final readonly class OctaneInstallable extends AbstractInstallable
{
    /**
     * @return array<int, string>
     */
    #[Override]
    public function dependencies(): array
    {
        return ['laravel/octane'];
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function postInstall(bool $useRecommended = true): array
    {
        if ($useRecommended) {
            return [
                'php artisan octane:install --server=frankenphp --no-interaction',
            ];
        }

        return [
            'php artisan octane:install',
        ];
    }

    /**
     * @return array<string, string|bool>
     */
    #[Override]
    public function environmentVariables(): array
    {
        return [
            'OCTANE_SERVER' => 'frankenphp',
        ];
    }

    #[Override]
    public function configFile(): string
    {
        return 'octane.php';
    }

    /**
     * @return Closure[]
     */
    #[Override]
    public function configs(): array
    {
        return [
            'server' => static fn () => env('OCTANE_SERVER', 'frankenphp'),
        ];
    }
}
