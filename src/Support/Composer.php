<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Symfony\Component\Process\ExecutableFinder;

class Composer
{
    private string $composer;

    public function __construct(private readonly ProcessRunner $process, private readonly string $cwd)
    {
        $this->initializeComposer();
    }

    public function getComposer(): string
    {
        return $this->composer;
    }

    public function updateDependencies(): void
    {
        $this->process->runCommands([
            "{$this->composer} update",
            "{$this->composer} bump",
            "{$this->composer} update",
        ], $this->cwd);
    }

    private function initializeComposer(): void
    {
        $this->composer = new ExecutableFinder()->find('composer') ?? 'composer';
    }
}
