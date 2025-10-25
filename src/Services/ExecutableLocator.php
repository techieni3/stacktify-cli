<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

use Illuminate\Support\ProcessUtils;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Locates system executables.
 *
 * This class finds executable binaries on the system.
 */
final readonly class ExecutableLocator
{
    /**
     * The ExecutableFinder instance.
     */
    private ExecutableFinder $finder;

    /**
     * Create a new ExecutableLocator instance.
     */
    public function __construct()
    {
        $this->finder = new ExecutableFinder();
    }

    /**
     * Find the PHP binary.
     */
    public function findPhp(): string
    {
        $phpBinary = (new PhpExecutableFinder)->find(false);

        return $phpBinary !== false
            ? ProcessUtils::escapeArgument($phpBinary)
            : 'php';
    }

    /**
     * Find the Composer binary.
     */
    public function findComposer(): string
    {
        $composer = $this->finder->find('composer');

        return $composer !== null
            ? ProcessUtils::escapeArgument($composer)
            : 'composer';
    }

    /**
     * Find the Git binary.
     */
    public function findGit(): string
    {
        $git = $this->finder->find('git');

        return $git !== null
            ? ProcessUtils::escapeArgument($git)
            : 'git';
    }

    /**
     * Find an executable binary.
     */
    public function findExecutable(string $binary): ?string
    {
        $executable = $this->finder->find($binary);

        return $executable !== null
            ? ProcessUtils::escapeArgument($executable)
            : null;
    }
}
