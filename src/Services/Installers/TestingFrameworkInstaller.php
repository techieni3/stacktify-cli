<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Techieni3\StacktifyCli\Enums\PestPlugin;
use Techieni3\StacktifyCli\Enums\TestingFramework;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;
use Techieni3\StacktifyCli\ValueObjects\Script;

/**
 * Installs the testing framework for the project.
 */
final class TestingFrameworkInstaller extends AbstractInstaller
{
    /**
     * @var array<string, string>
     */
    private array $env = ['PEST_NO_SUPPORT' => 'true'];

    public function install(): void
    {
        if ($this->config()->getTestingFramework() === TestingFramework::PhpUnit) {
            return;
        }

        $this->installPest();

        $this->convertExistingTestsToPest();

        $this->updatePestConfiguration();

        $this->removeRefreshDatabaseTraitFromTests();

        $this->installPestPlugins();

        $this->addComposerScripts([
            new Script(name: 'test', command: [
                '@php artisan config:clear --ansi',
                '@php artisan test --parallel',
            ]),
        ]);

        $this->commitChanges('chore: configure Pest testing framework');

        $this->notifySuccess('Pest installed successfully');
    }

    /**
     * Installs Pest and its dependencies.
     */
    private function installPest(): void
    {
        // Remove phpunit dependency
        $this->composer()->removeDevDependencies(['phpunit/phpunit --no-update'], $this->env);
        // Add pest dependency
        $this->composer()->installDevDependencies(['pestphp/pest', 'pestphp/pest-plugin-laravel'], $this->env);
        // Update dependencies
        $this->composer()->updateDependencies($this->env);
        // Init pest
        $this->process()->runCommands(
            commands: [sprintf('%s ./vendor/bin/pest --init', $this->php())],
            workingPath: $this->paths()->getInstallationDirectory(),
            env: $this->env
        );
    }

    /**
     * Converts existing PHPUnit tests to Pest tests.
     */
    private function convertExistingTestsToPest(): void
    {
        $this->composer()->installDevDependencies(['pestphp/pest-plugin-drift'], $this->env);

        $this->process()->runCommands(
            commands: [sprintf('%s ./vendor/bin/pest --drift', $this->php())],
            workingPath: $this->paths()->getInstallationDirectory(),
            env: $this->env
        );

        $this->composer()->removeDevDependencies(['pestphp/pest-plugin-drift'], $this->env);
    }

    /**
     * Updates the Pest configuration file.
     */
    private function updatePestConfiguration(): void
    {
        $pestConfigPath = $this->paths()->getPath('tests/Pest.php');

        FileEditor::replaceInFile($pestConfigPath, new Replacement(
            search: ' // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)',
            replace: '    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)',
        ));
    }

    /**
     * Removes the RefreshDatabase trait from the tests.
     */
    private function removeRefreshDatabaseTraitFromTests(): void
    {
        $testsDirectory = $this->paths()->getPath('tests');
        $directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsDirectory));

        /** @var SplFileInfo $testFile */
        foreach ($directoryIterator as $testFile) {
            if ($testFile->isDir()) {
                continue;
            }

            FileEditor::replaceInFile($testFile->getRealPath(), new Replacement(
                search: "\n\nuses(\Illuminate\Foundation\Testing\RefreshDatabase::class);",
                replace: '',
            ));
        }
    }

    /**
     * Installs the configured Pest plugins.
     */
    private function installPestPlugins(): void
    {
        $plugins = $this->config()->getPestPlugins();

        $dependencies = [];
        $requiresBrowserDependencies = false;

        foreach ($plugins as $plugin) {
            $dependencies[] = $plugin->package();

            if ($plugin === PestPlugin::BrowserTest) {
                $requiresBrowserDependencies = true;
            }
        }

        if ($dependencies !== []) {
            $this->composer()->installDevDependencies($dependencies, $this->env);
        }

        if ($requiresBrowserDependencies) {
            $this->installBrowserTestingDependencies();
        }
    }

    /**
     * Installs the browser testing dependencies.
     */
    private function installBrowserTestingDependencies(): void
    {
        $packageManager = $this->node();

        $packageManager->installDevDependencies(['playwright@latest']);
        $packageManager->execute('playwright', ['install']);
    }
}
