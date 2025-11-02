<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Git;

use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Exceptions\GitNotAvailable;
use Techieni3\StacktifyCli\Services\ExecutableLocator;
use Techieni3\StacktifyCli\Services\ProcessRunner;

/**
 * A Git client that executes Git commands.
 */
final readonly class GitRunner implements GitClient
{
    /**
     * The path to the Git executable.
     */
    private string $git;

    /**
     * Create a new GitRunner instance.
     */
    public function __construct(
        private ProcessRunner $proc,
        private string $cwd,
    ) {
        $this->git = new ExecutableLocator()->findGit();
    }

    /**
     * Initialize a new Git repository.
     */
    public function init(): void
    {
        $this->proc->execute([$this->git, 'init', '-q'], $this->cwd);
    }

    /**
     * Perform the initial commit.
     */
    public function createInitialCommit(): void
    {
        $branch = $this->defaultBranch();

        $commands = [
            'git add .',
            'git commit -q -m "set up a fresh Laravel app"',
            "git branch -M {$branch}",
        ];

        $this->proc->runCommands(
            commands: $commands,
            workingPath: $this->cwd,
            description: 'Initializing Git repository...'
        );
    }

    /**
     * Stage all changes.
     */
    public function addAll(): void
    {
        $this->proc->execute([$this->git, 'add', '-A'], $this->cwd);
    }

    /**
     * Commit the staged changes.
     */
    public function commit(string $message): void
    {
        $this->proc->execute([$this->git, 'commit', '-m', $message], $this->cwd);
    }

    /**
     * Check if Git is available.
     */
    public function isAvailable(): bool
    {
        return $this->proc->execute([$this->git, '--version'])->isSuccessful();
    }

    /**
     * Ensure Git is available.
     *
     * @throws GitNotAvailable
     */
    public function ensureAvailable(): void
    {
        if ( ! $this->isAvailable()) {
            throw new GitNotAvailable('Git not found.');
        }
    }

    /**
     * Check if a Git identity is configured.
     */
    public function hasIdentityConfigured(): bool
    {
        return $this->name() !== null && $this->email() !== null;
    }

    /**
     * Configure the Git user name.
     */
    public function configureName(string $name): void
    {
        $this->setConfig('user.name', $name);
    }

    /**
     * Configure the Git user email.
     */
    public function configureEmail(string $email): void
    {
        $this->setConfig('user.email', $email);
    }

    /**
     * Get the configured Git user name.
     */
    private function name(): ?string
    {
        return $this->readConfig('user.name');
    }

    /**
     * Get the configured Git user email.
     */
    private function email(): ?string
    {
        return $this->readConfig('user.email');
    }

    /**
     * Get the default Git branch name.
     */
    private function defaultBranch(): string
    {
        return $this->readConfig('init.defaultBranch') ?? 'main';
    }

    /**
     * Read a Git configuration value.
     */
    private function readConfig(string $key): ?string
    {
        $process = $this->proc->execute([$this->git, 'config', '--get', $key], $this->cwd);

        $output = mb_trim($process->getOutput());

        return $process->isSuccessful() && $output ? $output : null;
    }

    /**
     * Set a Git configuration value.
     */
    private function setConfig(string $key, string $value): void
    {
        $this->proc->execute([$this->git, 'config', '--local', $key, mb_trim($value)], $this->cwd);
    }
}
