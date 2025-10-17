<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

/**
 * A wrapper for running Composer commands.
 */
final readonly class Composer
{
    /**
     * The path to the Composer executable.
     */
    private string $composer;

    /**
     * Create a new Composer instance.
     */
    public function __construct(private ProcessRunner $process, private string $cwd)
    {
        $this->composer = new ExecutableLocator()->findComposer();
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
     * Install given dependencies
     */
    public function installDependencies(array $dependencies): void
    {
        $this->process->runCommands([
        sprintf("%s require %s", $this->composer, implode(" ", $dependencies)),
        ], $this->cwd);
    }

    /**
     * Install given dev dependencies
     */
    public function installDevDependencies(array $dependencies): void
    {
        $this->process->runCommands([
        sprintf("%s require --dev %s", $this->composer, implode(" ", $dependencies))
        ], $this->cwd);
    }
}
