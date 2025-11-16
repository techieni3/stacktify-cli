<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\Services\ProcessRunner;

it('executes a simple command successfully', function (): void {
    $runner = new ProcessRunner(isQuiet: false, isDecorated: true, isVerbose: false);

    $process = $runner->execute(['echo', 'hello']);

    expect($process->isSuccessful())->toBeTrue()
        ->and($process->getOutput())->toContain('hello');
});

it('executes command in specified working directory', function (): void {
    $runner = new ProcessRunner(isQuiet: false, isDecorated: true, isVerbose: false);
    $tempDir = sys_get_temp_dir();

    $process = $runner->execute(['pwd'], $tempDir);

    expect($process->isSuccessful())->toBeTrue();
})->skipOnWindows();

it('captures command output', function (): void {
    $runner = new ProcessRunner(isQuiet: false, isDecorated: true, isVerbose: false);

    $process = $runner->execute(['echo', 'test output']);

    expect($process->getOutput())->toBe("test output\n");
});

it('detects command failure', function (): void {
    $runner = new ProcessRunner(isQuiet: false, isDecorated: true, isVerbose: false);

    // Execute a command that will fail
    $process = $runner->execute(['ls', '/nonexistent-directory-'.uniqid('', true)]);

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getExitCode())->not->toBe(0);
});

it('excludes options from chmod commands', function (): void {
    $runner = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);

    // Use reflection to test private method
    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('shouldAddOption');

    expect($method->invoke($runner, 'chmod 755 file.txt'))->toBeFalse();
});

it('excludes options from rm commands', function (): void {
    $runner = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);

    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('shouldAddOption');

    expect($method->invoke($runner, 'rm -rf directory'))->toBeFalse();
});

it('excludes options from git commands', function (): void {
    $runner = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);

    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('shouldAddOption');

    expect($method->invoke($runner, 'git init'))->toBeFalse()
        ->and($method->invoke($runner, 'git add .'))->toBeFalse();
});

it('excludes options from pest commands', function (): void {
    $runner = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);

    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('shouldAddOption');

    expect($method->invoke($runner, './vendor/bin/pest'))->toBeFalse()
        ->and($method->invoke($runner, './vendor/bin/pest --coverage'))->toBeFalse();
});

it('allows options for composer commands', function (): void {
    $runner = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);

    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('shouldAddOption');

    expect($method->invoke($runner, 'composer install'))->toBeTrue()
        ->and($method->invoke($runner, 'composer require package'))->toBeTrue();
});

it('allows options for php artisan commands', function (): void {
    $runner = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);

    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('shouldAddOption');

    expect($method->invoke($runner, 'php artisan migrate'))->toBeTrue()
        ->and($method->invoke($runner, 'php artisan key:generate'))->toBeTrue();
});

it('adds no-ansi option when not decorated', function (): void {
    $runner = new ProcessRunner(isQuiet: false, isDecorated: false, isVerbose: false);

    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('prepareCommands');

    $commands = ['composer install', 'php artisan migrate'];
    $prepared = $method->invoke($runner, $commands);

    expect($prepared[0])->toContain('--no-ansi')
        ->and($prepared[1])->toContain('--no-ansi');
});

it('adds quiet option when quiet mode enabled', function (): void {
    $runner = new ProcessRunner(isQuiet: true, isDecorated: true, isVerbose: false);

    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('prepareCommands');

    $commands = ['composer install', 'php artisan migrate'];
    $prepared = $method->invoke($runner, $commands);

    expect($prepared[0])->toContain('--quiet')
        ->and($prepared[1])->toContain('--quiet');
});

it('does not add options when already decorated and not quiet', function (): void {
    $runner = new ProcessRunner(isQuiet: false, isDecorated: true, isVerbose: false);

    $reflection = new ReflectionClass($runner);
    $method = $reflection->getMethod('prepareCommands');

    $commands = ['composer install'];
    $prepared = $method->invoke($runner, $commands);

    expect($prepared[0])->toBe('composer install');
});

it('can determine if spinner can be displayed', function (): void {
    $runner1 = new ProcessRunner(isQuiet: false, isDecorated: true, isVerbose: false);
    $runner2 = new ProcessRunner(isQuiet: true, isDecorated: true, isVerbose: false);
    $runner3 = new ProcessRunner(isQuiet: false, isDecorated: true, isVerbose: true);

    $reflection = new ReflectionClass($runner1);
    $method = $reflection->getMethod('canDisplaySpinner');

    // Depends on whether pcntl_fork is available
    $canDisplay = $method->invoke($runner1);
    expect($canDisplay)->toBeBool()
        ->and($method->invoke($runner2))->toBeFalse()
        ->and($method->invoke($runner3))->toBeFalse();
});
