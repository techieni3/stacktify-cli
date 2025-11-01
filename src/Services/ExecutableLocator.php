<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

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

        return $phpBinary !== false ? $phpBinary : 'php';
    }

    /**
     * Find the Composer binary.
     */
    public function findComposer(): string
    {
        return $this->finder->find('composer') ?? 'composer';
    }

    /**
     * Find the Git binary.
     */
    public function findGit(): string
    {
        return $this->finder->find('git') ?? 'git';
    }

    /**
     * Find an executable binary.
     */
    public function findExecutable(string $binary): ?string
    {
        return $this->finder->find($binary);
    }
}
