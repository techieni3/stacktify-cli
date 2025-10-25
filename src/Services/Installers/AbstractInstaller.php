<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Exception;
use RuntimeException;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Enums\NodePackageManager;
use Techieni3\StacktifyCli\Services\Composer;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Services\ProcessRunner;

abstract class AbstractInstaller
{
    public function __construct(protected InstallerContext $context) {}

    abstract public function install(): void;

    protected function process(): ProcessRunner
    {
        return $this->context->processRunner();
    }

    protected function composer(): Composer
    {
        return $this->context->composer();
    }

    protected function config(): ScaffoldConfig
    {
        return $this->context->config();
    }

    protected function paths(): PathResolver
    {
        return $this->context->paths();
    }

    protected function git(): GitClient
    {
        return $this->context->git();
    }

    protected function php(): string
    {
        return $this->context->phpBinary();
    }

    protected function node(): NodePackageManager
    {
        return $this->config()->getPackageManager();
    }

    /**
     * Install composer dependencies.
     */
    protected function installPackages(array $dependencies, array $devDependencies): void
    {
        if ($dependencies !== []) {
            $this->composer()->installDependencies($dependencies);
        }

        if ($devDependencies !== []) {
            $this->composer()->installDevDependencies($devDependencies);
        }
    }

    /**
     * Publish stub files to the project.
     */
    protected function publishStubs(array $stubs): void
    {
        foreach ($stubs as $source => $destination) {
            $destinationPath = $this->paths()->getInstallationDirectory().DIRECTORY_SEPARATOR.$destination;
            FileEditor::copyFile($source, $destinationPath);
        }
    }

    protected function addScripts(array $scripts): void
    {
        try {
            $composerJson = FileEditor::json($this->paths()->getInstallationDirectory().DIRECTORY_SEPARATOR.'composer.json');
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to read composer.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }

        foreach ($scripts as $name => $command) {
            $composerJson->addScript($name, $command);
        }

        try {
            $composerJson->save();
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to save composer.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }
    }
}
