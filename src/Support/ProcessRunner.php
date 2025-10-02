<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Closure;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final readonly class ProcessRunner
{
    public function __construct(
        private bool $isQuiet,
        private bool $isDecorated
    ) {}

    public function runCommands(array $commands, ?string $workingPath = null, ?callable $onOutput = null, array $env = []): Process
    {
        $commands = $this->prepareCommands($commands);

        $process = $this->createProcess($commands, $workingPath, $env);

        $this->configureProcessTty($process, $onOutput);

        $process->run($onOutput);

        return $process;
    }

    public function writeOutput(OutputInterface $output): Closure
    {
        return static function ($type, $line) use ($output): void {
            $output->write('    '.$line);
        };
    }

    public function execute(array $command, ?string $cwd = null): Process
    {
        $process = new Process($command, $cwd);

        $process->run();

        return $process;
    }

    private function prepareCommands(array $commands): array
    {
        if ( ! $this->isDecorated) {
            $commands = $this->addNoAnsiOption($commands);
        }

        if ($this->isQuiet) {
            $commands = $this->addQuietOption($commands);
        }

        return $commands;
    }

    private function addNoAnsiOption(array $commands): array
    {
        return array_map(fn ($value) => $this->shouldAddOption($value) ? "{$value} --no-ansi" : $value, $commands);
    }

    private function addQuietOption(array $commands): array
    {
        return array_map(fn ($value) => $this->shouldAddOption($value) ? "{$value} --quiet" : $value, $commands);
    }

    private function shouldAddOption(string $value): bool
    {
        $commands = ['chmod', 'rm', 'git', './vendor/bin/pest'];

        if (array_any($commands, fn ($needle) => str_starts_with($value, $needle))) {
            return false;
        }

        return ! str_contains('./vendor/bin/pest', $value);
    }

    private function createProcess(array $commands, ?string $workingPath, array $env): Process
    {
        return Process::fromShellCommandline(implode(' && ', $commands), $workingPath, $env, null, null);
    }

    private function configureProcessTty(Process $process, ?callable $onOutput = null): void
    {
        if ( ! Process::isTtySupported()) {
            return;
        }

        try {
            $process->setTty(true);
        } catch (RuntimeException $e) {
            if ($onOutput !== null) {
                $onOutput('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }
    }
}
