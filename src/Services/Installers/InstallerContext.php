<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Services\Composer;
use Techieni3\StacktifyCli\Services\ExecutableLocator;
use Techieni3\StacktifyCli\Services\NodePackageManagerRunner;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Services\ProcessRunner;

/**
 * Aggregates shared services used by installers.
 */
final readonly class InstallerContext
{
    private string $phpBinary;

    public function __construct(
        private ProcessRunner $process,
        private Composer $composer,
        private NodePackageManagerRunner $nodePackageManager,
        private ScaffoldConfig $config,
        private PathResolver $paths,
        private GitClient $git,
        ?string $phpBinary = null,
    ) {
        $this->phpBinary = $phpBinary ?? new ExecutableLocator()->findPhp();
    }

    /**
     * Get the process runner.
     */
    public function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    /**
     * Get the composer instance.
     */
    public function composer(): Composer
    {
        return $this->composer;
    }

    /**
     * Get the scaffold config.
     */
    public function config(): ScaffoldConfig
    {
        return $this->config;
    }

    /**
     * Get the path resolver.
     */
    public function paths(): PathResolver
    {
        return $this->paths;
    }

    /**
     * Get the git client.
     */
    public function git(): GitClient
    {
        return $this->git;
    }

    /**
     * Get the PHP binary path.
     */
    public function phpBinary(): string
    {
        return $this->phpBinary;
    }

    /**
     * Get the node package manager runner.
     */
    public function nodePackageManager(): NodePackageManagerRunner
    {
        return $this->nodePackageManager;
    }
}
