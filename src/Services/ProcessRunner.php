<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

use Closure;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\spin;

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
        private bool $isDecorated,
        private bool $isVerbose,
    ) {}

    /**
     * Run a series of commands.
     *
     * @param  list<string>  $commands
     * @param  array<string, string|bool>  $env
     */
    public function runCommands(array $commands, ?string $workingPath = null, ?callable $onOutput = null, array $env = [], string $description = ''): Process
    {
        $commands = $this->prepareCommands($commands);

        $process = $this->createProcess($commands, $workingPath, $env);

        if ($this->canDisplaySpinner()) {

            spin(static fn (): int => $process->run(), $description);

            return $process;
        }

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
     *
     * @param  list<string>  $command
     */
    public function execute(array $command, ?string $cwd = null): Process
    {
        $process = new Process($command, $cwd);

        $process->run();

        return $process;
    }

    /**
     * Prepare the commands for execution.
     *
     * @param  list<string>  $commands
     * @return list<string>
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
     *
     * @param  list<string>  $commands
     * @return list<string>
     */
    private function addNoAnsiOption(array $commands): array
    {
        return array_map(fn (string $value): string => $this->shouldAddOption($value) ? "{$value} --no-ansi" : $value, $commands);
    }

    /**
     * Add the --quiet option to commands.
     *
     * @param  list<string>  $commands
     * @return list<string>
     */
    private function addQuietOption(array $commands): array
    {
        return array_map(fn (string $value): string => $this->shouldAddOption($value) ? "{$value} --quiet" : $value, $commands);
    }

    /**
     * Determine if an option should be added to a command.
     *
     * Excludes system commands (chmod, rm, git) and test frameworks (pest)
     * as they don't support --quiet and --no-ansi flags consistently.
     * Adding these flags to unsupported commands would cause errors.
     * *
     * @param  string  $value  The command string to check
     * @return bool True if the option can be safely added, false otherwise
     */
    private function shouldAddOption(string $value): bool
    {
        // Commands that don't support --quiet and --no-ansi options
        $commands = ['chmod', 'rm', 'git', './vendor/bin/pest'];

        // Check if the command starts with any excluded command
        if (array_any($commands, static fn ($needle): bool => str_starts_with($value, (string) $needle))) {
            return false;
        }

        // Additional check: exclude Pest test commands in any location
        return ! str_contains($value, './vendor/bin/pest');
    }

    /**
     * Create a new process instance.
     *
     * @param  list<string>  $commands
     * @param  array<string, string|bool>  $env
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

    /**
     * Determine if the spinner can be displayed.
     */
    private function canDisplaySpinner(): bool
    {
        return function_exists('pcntl_fork')
            && ! $this->isVerbose
            && ! $this->isQuiet;
    }
}
