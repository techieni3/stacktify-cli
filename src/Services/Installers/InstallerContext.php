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

    public function processRunner(): ProcessRunner
    {
        return $this->process;
    }

    public function composer(): Composer
    {
        return $this->composer;
    }

    public function config(): ScaffoldConfig
    {
        return $this->config;
    }

    public function paths(): PathResolver
    {
        return $this->paths;
    }

    public function git(): GitClient
    {
        return $this->git;
    }

    public function phpBinary(): string
    {
        return $this->phpBinary;
    }

    public function nodePackageManager(): NodePackageManagerRunner
    {
        return $this->nodePackageManager;
    }
}
