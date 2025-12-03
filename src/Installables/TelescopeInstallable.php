<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Installables;

use Closure;
use Exception;
use Override;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\Installers\InstallerContext;

/**
 * An installable for Laravel Octane.
 *
 * @see https://laravel.com/docs/telescope
 * @see migrations/...._create_telescope_entries_table.php
 */
final readonly class TelescopeInstallable extends AbstractInstallable
{
    /**
     * @return array<int, string>
     */
    #[Override]
    public function devDependencies(): array
    {
        return ['laravel/telescope'];
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function postInstall(): array
    {
        return [
            'php artisan telescope:install',
            'php artisan migrate',
        ];
    }

    /**
     * @return array<string, string|bool>
     */
    #[Override]
    public function environmentVariables(): array
    {
        return [
            'TELESCOPE_ENABLED' => false,
        ];
    }

    #[Override]
    public function configFile(): string
    {
        return 'telescope.php';
    }

    /**
     * @return Closure[]
     */
    #[Override]
    public function configs(): array
    {
        return [
            'enabled' => static fn () => env('TELESCOPE_ENABLED', false),
        ];
    }

    /**
     * @return array<int, string>
     */
    #[Override]
    public function composerPostUpdateScripts(): array
    {
        return [
            'post-update-cmd' => '@php artisan vendor:publish --tag=laravel-assets --ansi --force',
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
            'useStatements' => [],
            'register' => ['$this->configureTelescope();'],
            'boot' => [],
            'newMethods' => [
                <<<'PHP'
                    /**
                     * Configure telescope for local development.
                     */
                    private function configureTelescope(): void
                    {
                        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
                             $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                             $this->app->register(TelescopeServiceProvider::class);
                         };
                    }
                PHP,
            ],
        ];
    }

    #[Override]
    public function customInstall(InstallerContext $context): void
    {
        // enable telescope in a local .env file
        FileEditor::env($context->paths()->getEnvPath())
            ->setBoolean('TELESCOPE_ENABLED', true)
            ->save();

        // remove telescope provider from provider.php
        FileEditor::text($context->paths()->getPath('bootstrap/providers.php'))
            ->removeLine(static fn ($line): bool => str_contains((string) $line, 'TelescopeServiceProvider'))
            ->save();

        try {
            // remove telescope from laravel auto discovery
            FileEditor::json($context->paths()->getPath('composer.json'))
                ->append('extra.laravel.dont-discover', 'laravel/telescope')
                ->save();
        } catch (Exception) {
            // Silently ignore errors
        }

        // add schedular to purge telescope logs
        FileEditor::text($context->paths()->getPath('routes/console.php'))
            ->append(PHP_EOL."Illuminate\Support\Facades\Schedule::command('telescope:prune --hours=48')->daily();".PHP_EOL);

        // set the default avatar for telescope use
        FileEditor::copyFile(
            sourceFile: __DIR__.'/../../stubs/Telescope/generic-avatar.jpg',
            destination: $context->paths()->getPath('public/generic-avatar.jpg')
        );

        FileEditor::serviceProvider($context->paths()->getPath('app/Providers/TelescopeServiceProvider.php'))
            ->addUseStatements([
                'Laravel\Telescope\Telescope',
            ])
            ->addToRegister([
                "Telescope::avatar(fn (?string \$id, ?string \$email) => '/generic-avatar.jpg');",
            ])
            ->save();

    }
}
