<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

use Techieni3\StacktifyCli\Enums\NodePackageManager;

final readonly class NodePackageManagerRunner
{
    public function __construct(
        private NodePackageManager $packageManager,
        private ProcessRunner $process,
        private string $cwd
    ) {}

    /**
     * Install given dependencies as production dependencies.
     *
     * @param  array<int, mixed>  $dependencies
     */
    public function installDependencies(array $dependencies, array $env = []): void
    {
        if ($dependencies === []) {
            return;
        }

        $this->process->runCommands(
            commands: [
                sprintf('%s %s', $this->packageManager->addCommand(), implode(' ', $dependencies)),
            ],
            workingPath: $this->cwd,
            env: $env,
            description: sprintf('Adding %s', count($dependencies) > 1 ? $dependencies[0].'...' : $dependencies[0])
        );
    }

    /**
     * Install given dependencies as development dependencies.
     *
     * @param  array<int, mixed>  $dependencies
     */
    public function installDevDependencies(array $dependencies, array $env = []): void
    {
        if ($dependencies === []) {
            return;
        }

        $this->process->runCommands(
            commands: [
                sprintf('%s %s', $this->packageManager->addDevCommand(), implode(' ', $dependencies)),
            ],
            workingPath: $this->cwd,
            env: $env,
            description: sprintf('Adding %s', count($dependencies) > 1 ? $dependencies[0].'...' : $dependencies[0])
        );
    }

    /**
     * Remove given packages.
     *
     * @param  array<int, string>  $dependencies
     */
    public function removeDependencies(array $dependencies, array $env = []): void
    {
        if ($dependencies === []) {
            return;
        }

        $this->process->runCommands(
            commands: [
                sprintf('%s %s', $this->packageManager->removeCommand(), implode(' ', $dependencies)),
            ],
            workingPath: $this->cwd,
            env: $env,
            description: sprintf('Removing %s', count($dependencies) > 1 ? $dependencies[0].'...' : $dependencies[0])
        );
    }

    /**
     * Update all packages.
     */
    public function updateDependencies(array $env = []): void
    {
        $this->process->runCommands(
            commands: [$this->packageManager->updateCommand()],
            workingPath: $this->cwd,
            env: $env,
            description: 'Updating packages...'
        );
    }

    /**
     * Run a script defined in package.json.
     */
    public function run(string $script, array $env = []): void
    {
        $command = $this->packageManager->runCommand()." {$script}";

        $this->process->runCommands(
            commands: [$command],
            workingPath: $this->cwd,
            env: $env,
            description: "Running script: {$script}"
        );
    }

    /**
     * Build the project.
     */
    public function build(array $env = []): void
    {
        $this->process->runCommands(
            commands: [$this->packageManager->buildCommand()],
            workingPath: $this->cwd,
            env: $env,
            description: 'Building assets...'
        );
    }

    /**
     * Execute a package (npx equivalent).
     */
    public function execute(string $package, array $args = [], array $env = []): void
    {
        $command = $this->packageManager->executeCommand()." {$package}";

        if ($args !== []) {
            $command .= ' '.implode(' ', $args);
        }

        $this->process->runCommands(
            commands: [$command],
            workingPath: $this->cwd,
            env: $env,
            description: "Executing: {$package}"
        );
    }
}
