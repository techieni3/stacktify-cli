<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Techieni3\StacktifyCli\Services\FileEditors\JsonFileEditor;
use Techieni3\StacktifyCli\ValueObjects\Script;

$destinationDirectory = dirname(__DIR__).'/../../Workspace';

beforeEach(function () use ($destinationDirectory): void {
    // check workspace directory existence
    new Filesystem()->ensureDirectoryExists($destinationDirectory);

    new Filesystem()->copy(dirname(__DIR__).'/../../Fixtures/composer.json', $destinationDirectory.'/composer.json');
    new Filesystem()->copy(dirname(__DIR__).'/../../Fixtures/package.json', $destinationDirectory.'/package.json');
});

afterEach(function () use ($destinationDirectory): void {
    if (file_exists($destinationDirectory.'/composer.json')) {
        unlink($destinationDirectory.'/composer.json');
    }

    if (file_exists($destinationDirectory.'/package.json')) {
        unlink($destinationDirectory.'/package.json');
    }
});

it('returns false when saving without changes', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    expect($composerJson->save())->toBeFalse();
});

it('adds a new script', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->addScript(new Script('test', 'php artisan test'));

    expect($composerJson->save())->toBeTrue()
        ->and(file_get_contents($destinationDirectory.'/composer.json'))->toContain('"test": "php artisan test"');

});

it('adds a script with array command', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $command = [
        'npm run dev',
        'npm run build',
    ];
    $composerJson->addScript(new Script('scripts', $command));

    expect($composerJson->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/composer.json');

    expect($content)->toContain('"npm run dev"')
        ->and($content)->toContain('"npm run build"');
});

it('returns false when saving package.json without changes', function () use ($destinationDirectory): void {
    $packageJson = new JsonFileEditor($destinationDirectory.'/package.json');

    expect($packageJson->save())->toBeFalse();
});

it('adds a new script to package.json', function () use ($destinationDirectory): void {
    $packageJson = new JsonFileEditor($destinationDirectory.'/package.json');

    $packageJson->addScript(new Script('test', 'vitest'));

    expect($packageJson->save())
        ->toBeTrue()
        ->and(file_get_contents($destinationDirectory.'/package.json'))
        ->toContain('"test": "vitest"');
});

it('adds a script with array command to package.json', function () use ($destinationDirectory): void {
    $packageJson = new JsonFileEditor($destinationDirectory.'/package.json');

    $command = ['vite build', 'vite preview'];
    $packageJson->addScript(new Script('preview', $command));

    expect($packageJson->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/package.json');

    expect($content)
        ->toContain('"vite build"')
        ->and($content)
        ->toContain('"vite preview"');
});

it('preserves existing scripts in package.json when adding new ones', function () use ($destinationDirectory): void {
    $packageJson = new JsonFileEditor($destinationDirectory.'/package.json');

    $packageJson->addScript(new Script('test', 'vitest'));
    $packageJson->addScript(new Script('lint', 'eslint .'));

    expect($packageJson->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/package.json');

    expect($content)
        ->toContain('"dev": "vite"')
        ->and($content)
        ->toContain('"build": "vite build"')
        ->and($content)
        ->toContain('"test": "vitest"')
        ->and($content)
        ->toContain('"lint": "eslint ."');
});

it('properly writes composer.json file', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->addScript(new Script('coverage', 'php artisan test --coverage'))
        ->save();

    $json = file_get_contents($destinationDirectory.'/composer.json');
    $decoded = json_decode($json, true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE)
        ->and($decoded)->toBeArray()
        ->and($decoded)->toHaveKey('scripts')
        ->and($decoded['scripts'])->toHaveKey('coverage', 'php artisan test --coverage');
});

it('properly writes package.json file', function () use ($destinationDirectory): void {
    $packageJson = new JsonFileEditor($destinationDirectory.'/package.json');

    $packageJson->addScript(new Script('lint', 'eslint .'))
        ->save();

    $json = file_get_contents($destinationDirectory.'/package.json');
    $decoded = json_decode($json, true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE)
        ->and($decoded)->toBeArray()
        ->and($decoded)->toHaveKey('scripts')
        ->and($decoded['scripts'])->toHaveKey('lint', 'eslint .');
});
