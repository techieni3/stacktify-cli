<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Services\Composer;
use Techieni3\StacktifyCli\Services\ExecutableLocator;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Services\ProcessRunner;

abstract class AbstractInstaller
{
    /**
     * The path to the PHP binary.
     */
    protected readonly string $php;

    public function __construct(
        protected ProcessRunner $process,
        protected Composer $composer,
        protected ScaffoldConfig $config,
        protected PathResolver $paths,
        protected GitClient $git,
    ) {
        $this->php = new ExecutableLocator()->findPhp();
    }

    abstract public function install(): void;

    /**
     * Install composer dependencies.
     */
    protected function installPackages(array $dependencies, array $devDependencies): void
    {
        if ($dependencies !== []) {
            $this->composer->installDependencies($dependencies);
        }

        if ($devDependencies !== []) {
            $this->composer->installDevDependencies($devDependencies);
        }
    }

    /**
     * Publish stub files to the project.
     */
    protected function publishStubs(array $stubs): void
    {
        foreach ($stubs as $source => $destination) {
            $destinationPath = $this->paths->getInstallationDirectory().DIRECTORY_SEPARATOR.$destination;
            FileEditor::copyFile($source, $destinationPath);
        }
    }
}
