<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Closure;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * A wrapper for running shell commands.
 */
final readonly class ProcessRunner
{
    /**
     * Create a new ProcessRunner instance.
     */
    public function __construct(
        private bool $isQuiet,
        private bool $isDecorated
    ) {}

    /**
     * Run a series of commands.
     */
    public function runCommands(array $commands, ?string $workingPath = null, ?callable $onOutput = null, array $env = []): Process
    {
        $commands = $this->prepareCommands($commands);

        $process = $this->createProcess($commands, $workingPath, $env);

        $this->configureProcessTty($process, $onOutput);

        $process->run($onOutput);

        return $process;
    }

    /**
     * Get a closure for writing output.
     */
    public function writeOutput(OutputInterface $output): Closure
    {
        return static function ($type, string $line) use ($output): void {
            $output->write('    '.$line);
        };
    }

    /**
     * Execute a single command.
     */
    public function execute(array $command, ?string $cwd = null): Process
    {
        $process = new Process($command, $cwd);

        $process->run();

        return $process;
    }

    /**
     * Prepare the commands for execution.
     */
    private function prepareCommands(array $commands): array
    {
        if ( ! $this->isDecorated) {
            $commands = $this->addNoAnsiOption($commands);
        }

        if ($this->isQuiet) {
            return $this->addQuietOption($commands);
        }

        return $commands;
    }

    /**
     * Add the --no-ansi option to commands.
     */
    private function addNoAnsiOption(array $commands): array
    {
        return array_map(fn ($value) => $this->shouldAddOption($value) ? "{$value} --no-ansi" : $value, $commands);
    }

    /**
     * Add the --quiet option to commands.
     */
    private function addQuietOption(array $commands): array
    {
        return array_map(fn ($value) => $this->shouldAddOption($value) ? "{$value} --quiet" : $value, $commands);
    }

    /**
     * Determine if an option should be added to a command.
     */
    private function shouldAddOption(string $value): bool
    {
        $commands = ['chmod', 'rm', 'git', './vendor/bin/pest'];

        if (array_any($commands, static fn ($needle): bool => str_starts_with($value, (string) $needle))) {
            return false;
        }

        return ! str_contains('./vendor/bin/pest', $value);
    }

    /**
     * Create a new process instance.
     */
    private function createProcess(array $commands, ?string $workingPath, array $env): Process
    {
        return Process::fromShellCommandline(implode(' && ', $commands), $workingPath, $env, null, null);
    }

    /**
     * Configure the TTY for the process.
     */
    private function configureProcessTty(Process $process, ?callable $onOutput = null): void
    {
        if ( ! Process::isTtySupported()) {
            return;
        }

        try {
            $process->setTty(true);
        } catch (RuntimeException $runtimeException) {
            if ($onOutput !== null) {
                $onOutput('  <bg=yellow;fg=black> WARN </> '.$runtimeException->getMessage().PHP_EOL);
            }
        }
    }
}
