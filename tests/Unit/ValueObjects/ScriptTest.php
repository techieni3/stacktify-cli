<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\ValueObjects\Script;

it('creates a valid script with string command', function (): void {
    $script = new Script('test', 'php artisan test');

    expect($script->getName())->toBe('test')
        ->and($script->getCommand())->toBe('php artisan test');
});

it('creates a valid script with array command', function (): void {
    $commands = [
        'npm run dev',
        'npm run build',
    ];
    $script = new Script('scripts', $commands);

    expect($script->getName())->toBe('scripts')
        ->and($script->getCommand())->toBe($commands);
});

it('creates a valid script with single element array command', function (): void {
    $command = ['npm start'];
    $script = new Script('start', $command);

    expect($script->getName())->toBe('start')
        ->and($script->getCommand())->toBe($command);
});

it('trims whitespace from name', function (): void {
    $script = new Script('  test  ', 'php artisan test');

    expect($script->getName())->toBe('test');
});

it('trims whitespace from command', function (): void {
    $script = new Script('test', '    php artisan test');

    expect($script->getCommand())->toBe('php artisan test');
});

it('trims whitespace from array command', function (): void {
    $script = new Script('test', ['    php artisan test']);

    expect($script->getCommand())->toBe(['php artisan test']);
});

it('trims whitespace from string command but validates non-empty', function (): void {
    expect(static fn (): Script => new Script('test', '   '))
        ->toThrow(InvalidArgumentException::class, 'Script command cannot be empty.');
});

it('throws exception when name is empty', function (): void {
    expect(static fn (): Script => new Script('', 'php artisan test'))
        ->toThrow(InvalidArgumentException::class, 'Script name cannot be empty.');
});

it('throws exception when name is only whitespace', function (): void {
    expect(static fn (): Script => new Script('   ', 'php artisan test'))
        ->toThrow(InvalidArgumentException::class, 'Script name cannot be empty.');
});

it('throws exception when string command is empty', function (): void {
    expect(static fn (): Script => new Script('test', ''))
        ->toThrow(InvalidArgumentException::class, 'Script command cannot be empty.');
});

it('throws exception when string command is only whitespace', function (): void {
    expect(static fn (): Script => new Script('test', '   '))
        ->toThrow(InvalidArgumentException::class, 'Script command cannot be empty.');
});

it('throws exception when array command is empty', function (): void {
    expect(static fn (): Script => new Script('test', []))
        ->toThrow(InvalidArgumentException::class, 'Script command array cannot be empty.');
});

it('throws exception when array command has string keys', function (): void {
    expect(static fn (): Script => new Script('test', ['dev' => 'npm start']))
        ->toThrow(InvalidArgumentException::class, 'Script command array must be sequential (not associative).');
});

it('throws exception when array command has non-string values', function (): void {
    expect(static fn (): Script => new Script('test', [123]))
        ->toThrow(InvalidArgumentException::class, 'Script command array values must be strings.');
});

it('handles command with special characters', function (): void {
    $command = 'npm run test -- --coverage && npm run lint';
    $script = new Script('test:coverage', $command);

    expect($script->getName())->toBe('test:coverage')
        ->and($script->getCommand())->toBe($command);
});

it('handles long command strings', function (): void {
    $command = 'php artisan migrate:fresh --seed && php artisan test && npm run build';
    $script = new Script('setup', $command);

    expect($script->getName())->toBe('setup')
        ->and($script->getCommand())->toBe($command);
});
