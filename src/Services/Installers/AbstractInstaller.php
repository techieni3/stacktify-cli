<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Exception;
use RuntimeException;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Services\Composer;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\NodePackageManagerRunner;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Services\ProcessRunner;
use Techieni3\StacktifyCli\ValueObjects\Script;

/**
 * Base class for installers.
 */
abstract class AbstractInstaller
{
    public function __construct(protected InstallerContext $context) {}

    /**
     * Install the service.
     */
    abstract public function install(): void;

    /**
     * Get the process runner.
     */
    protected function process(): ProcessRunner
    {
        return $this->context->processRunner();
    }

    /**
     * Get the composer instance.
     */
    protected function composer(): Composer
    {
        return $this->context->composer();
    }

    /**
     * Get the scaffold config.
     */
    protected function config(): ScaffoldConfig
    {
        return $this->context->config();
    }

    /**
     * Get the path resolver.
     */
    protected function paths(): PathResolver
    {
        return $this->context->paths();
    }

    /**
     * Get the git client.
     */
    protected function git(): GitClient
    {
        return $this->context->git();
    }

    /**
     * Get the PHP binary path.
     */
    protected function php(): string
    {
        return $this->context->phpBinary();
    }

    /**
     * Get the node package manager runner.
     */
    protected function node(): NodePackageManagerRunner
    {
        return $this->context->nodePackageManager();
    }

    /**
     * Install composer dependencies.
     *
     * @param  array<int, string>  $dependencies
     * @param  array<int, string>  $devDependencies
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
     *
     * @param  array<string, string>  $stubs
     */
    protected function publishStubs(array $stubs): void
    {
        foreach ($stubs as $source => $destination) {
            $destinationPath = $this->paths()->getInstallationDirectory().DIRECTORY_SEPARATOR.$destination;
            FileEditor::copyFile($source, $destinationPath);
        }
    }

    /**
     * Add scripts to the composer.json file.
     *
     * @param  array<Script>  $scripts
     */
    protected function addScripts(array $scripts): void
    {
        try {
            $composerJson = FileEditor::json($this->paths()->getInstallationDirectory().DIRECTORY_SEPARATOR.'composer.json');
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to read composer.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }

        foreach ($scripts as $script) {
            $composerJson->addScript($script);
        }

        try {
            $composerJson->save();
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to save composer.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }
    }
}
