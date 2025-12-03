<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Installables;

use Closure;
use Override;

/**
 * An installable for Laravel User Permissions.
 *
 * @see https://github.com/techieni3/laravel-user-permissions
 */
final readonly class RoleAndPermissionsInstallable extends AbstractInstallable
{
    /**
     * @return array<int, string>
     */
    #[Override]
    public function dependencies(): array
    {
        return ['techieni3/laravel-user-permissions'];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function stubs(): array
    {
        return [
            __DIR__.'/../../stubs/RoleAndPermissions/PermissionsSeeder.stub' => 'database/seeders/PermissionsSeeder.php',
        ];
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function postInstall(): array
    {
        return [
            'php artisan install:permissions',
            'php artisan migrate',
            'php artisan db:seed --class=PermissionsSeeder',
        ];
    }

    /**
     * @return array<string, string|bool>
     */
    #[Override]
    public function environmentVariables(): array
    {
        return [
            'PERMISSIONS_MANAGER_DASHBOARD_ENABLED' => true,
            'PERMISSIONS_MANAGER_DASHBOARD_PATH' => 'permissions-manager',
            'PERMISSIONS_MANAGER_GATE' => 'viewPermissionsDashboard',
            'PERMISSIONS_EVENTS_ENABLED' => false,
        ];
    }

    #[Override]
    public function configFile(): string
    {
        return 'permissions.php';
    }

    /**
     * @return Closure[]
     */
    #[Override]
    public function configs(): array
    {
        return [
            'events_enabled' => static fn () => env('PERMISSIONS_EVENTS_ENABLED', false),
        ];
    }

    /**
     * @return array{
     *     useStatements: array<string>,
     *     register: array<string>,
     *     boot: array<string>,
     *     newMethods: array<string>
     * }
     */
    #[Override]
    public function serviceProviderConfig(): array
    {
        return [
            'useStatements' => ['Illuminate\Support\Facades\Gate'],
            'register' => [],
            'boot' => ['$this->configurePermissionsDashboardGate();'],
            'newMethods' => [
                <<<'PHP'
                    /**
                     * Configure a permission dashboard gate.
                     */
                    private function configurePermissionsDashboardGate(): void
                    {
                        Gate::define('viewPermissionsDashboard', fn ($user) => $user->hasPermission('view_permissions_dashboard'));
                    }
                PHP,
            ],
        ];
    }
}
