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
    public function path(): string
    {
        return $this->composer;
    }

    /**
     * Update the Composer dependencies.
     *
     * @param  array<string>  $env
     */
    public function updateDependencies(array $env = []): void
    {
        $this->process->runCommands(
            commands: [
                "{$this->composer} update",
                "{$this->composer} bump",
                "{$this->composer} update",
            ],
            workingPath: $this->cwd,
            env: $env,
            description: 'Updating Composer dependencies...'
        );
    }

    /**
     * Install given dependencies
     *
     * @param  array<int, mixed>  $dependencies
     * @param  array<string>  $env
     */
    public function installDependencies(array $dependencies, array $env = []): void
    {
        if ($dependencies === []) {
            return;
        }

        $this->process->runCommands(
            commands: [
                sprintf('%s require %s', $this->composer, implode(' ', $dependencies)),
            ],
            workingPath: $this->cwd,
            env: $env,
            description: sprintf('Installing %s', count($dependencies) > 1 ? $dependencies[0].'...' : $dependencies[0])
        );
    }

    /**
     * Remove given dependencies
     *
     * @param  array<int, mixed>  $dependencies
     * @param  array<string>  $env
     */
    public function removeDependencies(array $dependencies, array $env = []): void
    {
        if ($dependencies === []) {
            return;
        }

        $this->process->runCommands(
            commands: [
                sprintf('%s remove %s', $this->composer, implode(' ', $dependencies)),
            ],
            workingPath: $this->cwd,
            env: $env,
            description: sprintf('Removing %s', count($dependencies) > 1 ? $dependencies[0].'...' : $dependencies[0])
        );
    }

    /**
     * Install given dev dependencies
     *
     * @param  array<int, mixed>  $dependencies
     * @param  array<string>  $env
     */
    public function installDevDependencies(array $dependencies, array $env = []): void
    {
        if ($dependencies === []) {
            return;
        }

        $this->process->runCommands(
            commands: [
                sprintf('%s require --dev %s', $this->composer, implode(' ', $dependencies)),
            ],
            workingPath: $this->cwd,
            env: $env,
            description: sprintf('Installing %s', count($dependencies) > 1 ? $dependencies[0].'...' : $dependencies[0])
        );
    }

    /**
     * Remove given dev dependencies
     *
     * @param  array<int, mixed>  $dependencies
     * @param  array<string>  $env
     */
    public function removeDevDependencies(array $dependencies, array $env = []): void
    {
        if ($dependencies === []) {
            return;
        }

        $this->process->runCommands(
            commands: [
                sprintf('%s remove --dev %s', $this->composer, implode(' ', $dependencies)),
            ],
            workingPath: $this->cwd,
            env: $env,
            description: sprintf('Removing %s', count($dependencies) > 1 ? $dependencies[0].'...' : $dependencies[0])
        );
    }
}
