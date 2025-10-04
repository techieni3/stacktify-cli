<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Symfony\Component\Process\ExecutableFinder;

/**
 * A wrapper for running Composer commands.
 */
class Composer
{
    /**
     * The path to the Composer executable.
     */
    private string $composer;

    /**
     * Create a new Composer instance.
     */
    public function __construct(private readonly ProcessRunner $process, private readonly string $cwd)
    {
        $this->initializeComposer();
    }

    /**
     * Get the Composer executable path.
     */
    public function getComposer(): string
    {
        return $this->composer;
    }

    /**
     * Update the Composer dependencies.
     */
    public function updateDependencies(): void
    {
        $this->process->runCommands([
            "{$this->composer} update",
            "{$this->composer} bump",
            "{$this->composer} update",
        ], $this->cwd);
    }

    /**
     * Find the Composer executable.
     */
    private function initializeComposer(): void
    {
        $this->composer = new ExecutableFinder()->find('composer') ?? 'composer';
    }
}
