<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Techieni3\StacktifyCli\Enums\TestingFramework;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

final class TestingFrameworkInstaller extends AbstractInstaller
{
    /**
     * @var array<string, string>
     */
    private array $env = ['PEST_NO_SUPPORT' => 'true'];

    public function install(): void
    {
        if ($this->config->getTestingFramework() === TestingFramework::PhpUnit) {
            return;
        }

        $this->installPest();

        $this->convertExistingTestsToPest();

        $this->updatePestConfiguration();

        $this->removeRefreshDatabaseTraitFromTests();

        $this->installPestPlugins();

        $this->addComposerScripts();
    }

    private function addComposerScripts(): void {}

    private function installPest(): void
    {
        // Remove phpunit dependency
        $this->composer->removeDevDependencies(['phpunit/phpunit --no-update'], $this->env);
        // Add pest dependency
        $this->composer->installDevDependencies(['pestphp/pest', 'pestphp/pest-plugin-laravel'], $this->env);
        // Update dependencies
        $this->composer->updateDependencies($this->env);
        // Init pest
        $this->process->runCommands(
            commands: [sprintf('%s ./vendor/bin/pest --init', $this->php)],
            workingPath: $this->paths->getInstallationDirectory(),
            env: $this->env
        );
    }

    private function convertExistingTestsToPest(): void
    {
        $this->composer->installDevDependencies(['pestphp/pest-plugin-drift'], $this->env);

        $this->process->runCommands(
            commands: [sprintf('%s ./vendor/bin/pest --drift', $this->php)],
            workingPath: $this->paths->getInstallationDirectory(),
            env: $this->env
        );

        $this->composer->removeDevDependencies(['pestphp/pest-plugin-drift'], $this->env);
    }

    private function updatePestConfiguration(): void
    {
        $pestConfigPath = $this->paths->getPath('tests/Pest.php');

        FileEditor::replaceInFile($pestConfigPath, new Replacement(
            search: ' // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)',
            replace: '    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)',
        ));
    }

    private function removeRefreshDatabaseTraitFromTests(): void
    {
        $testsDirectory = $this->paths->getPath('tests');
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

    private function installPestPlugins(): void
    {
        $plugins = $this->config->getPestPlugins();

        $dependencies = [];

        foreach ($plugins as $plugin) {
            $dependencies[] = $plugin->package();
        }

        if ($dependencies !== []) {
            $this->composer->installDevDependencies($dependencies, $this->env);
        }
    }
}
