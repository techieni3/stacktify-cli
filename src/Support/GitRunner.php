<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Symfony\Component\Process\ExecutableFinder;
use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Exceptions\GitNotAvailable;

final class GitRunner implements GitClient
{
    private string $git;

    public function __construct(
        private readonly ProcessRunner $proc,
        private readonly string $cwd,
    ) {
        $this->git = new ExecutableFinder()->find('git') ?? 'git';
    }

    public function init(): void
    {
        $this->proc->execute([$this->git, 'init', '-q'], $this->cwd);
    }

    public function initializeRepository(): void
    {
        $branch = $this->defaultBranch();

        $commands = [
            'git add .',
            'git commit -q -m "Set up a fresh Laravel app"',
            "git branch -M {$branch}",
        ];

        $this->proc->runCommands(commands: $commands, workingPath: $this->cwd);
    }

    public function addAll(): void
    {
        $this->proc->execute([$this->git, 'add', '-A'], $this->cwd);
    }

    public function commit(string $message): void
    {
        $this->proc->execute([$this->git, 'commit', '-m', $message], $this->cwd);
    }

    public function isAvailable(): bool
    {
        return $this->proc->execute([$this->git, '--version'])->isSuccessful();
    }

    public function ensureAvailable(): void
    {
        if ( ! $this->isAvailable()) {
            throw new GitNotAvailable('Git not found.');
        }
    }

    public function hasIdentityConfigured(): bool
    {
        return $this->name() !== null && $this->email() !== null;
    }

    public function configureName(string $name): void
    {
        $this->setConfig('user.name', $name);
    }

    public function configureEmail(string $email): void
    {
        $this->setConfig('user.email', $email);
    }

    private function name(): ?string
    {
        return $this->readConfig('user.name');
    }

    private function email(): ?string
    {
        return $this->readConfig('user.email');
    }

    private function defaultBranch(): string
    {
        return $this->readConfig('init.defaultBranch') ?? 'main';
    }

    private function readConfig(string $key): ?string
    {
        $process = $this->proc->execute([$this->git, 'config', '--get', $key]);

        $output = mb_trim($process->getOutput());

        return $process->isSuccessful() && $output ? $output : null;
    }

    private function setConfig(string $key, string $value): void
    {
        $this->proc->execute([$this->git, 'config', '--local', $key, mb_trim($value)]);
    }
}
