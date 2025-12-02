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

it('appends a command to a non-existent script', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->appendToScript('post-update-cmd', '@php artisan optimize');

    expect($composerJson->save())->toBeTrue();

    $content = json_decode(file_get_contents($destinationDirectory.'/composer.json'), true);

    expect($content['scripts']['post-update-cmd'])
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('@php artisan optimize');
});

it('appends a command to an existing script with string value', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    // First add a script as a string
    $composerJson->addScript(new Script('post-update-cmd', '@php artisan migrate'));
    $composerJson->save();

    // Reload the file
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    // Append to it - should convert to array
    $composerJson->appendToScript(
        'post-update-cmd',
        '@php artisan optimize',
    );

    expect($composerJson->save())->toBeTrue();

    $content = json_decode(file_get_contents($destinationDirectory.'/composer.json'), true);

    expect($content['scripts']['post-update-cmd'])
        ->toBeArray()
        ->toHaveCount(2)
        ->toContain('@php artisan migrate')
        ->toContain('@php artisan optimize');
});

it('appends a command to an existing script with array value', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    // Add a script with array value
    $composerJson->addScript(
        new Script('post-update-cmd', [
            '@php artisan migrate',
            '@php artisan cache:clear',
        ]),
    );

    $composerJson->save();

    // Reload and append
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->appendToScript('post-update-cmd', '@php artisan optimize');

    expect($composerJson->save())->toBeTrue();

    $content = json_decode(file_get_contents($destinationDirectory.'/composer.json'), true);

    expect($content['scripts']['post-update-cmd'])
        ->toBeArray()
        ->toHaveCount(3)
        ->toContain('@php artisan migrate')
        ->toContain('@php artisan cache:clear')
        ->toContain('@php artisan optimize');
});

it('appends multiple commands at once', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->appendToScript('post-update-cmd', [
        '@php artisan migrate',
        '@php artisan optimize',
        '@php artisan cache:clear',
    ]);

    expect($composerJson->save())->toBeTrue();

    $content = json_decode(file_get_contents($destinationDirectory.'/composer.json'), true);

    expect($content['scripts']['post-update-cmd'])
        ->toBeArray()
        ->toHaveCount(3)
        ->toContain('@php artisan migrate')
        ->toContain('@php artisan optimize')
        ->toContain('@php artisan cache:clear');
});

it('appends multiple commands to existing script', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    // Add initial script
    $composerJson->addScript(new Script('post-install-cmd', '@php artisan key:generate'));

    expect($composerJson->save())->toBeTrue();

    // Reload and append multiple
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');
    $composerJson->appendToScript('post-install-cmd', [
        '@php artisan migrate',
        '@php artisan db:seed',
    ]);

    expect($composerJson->save())->toBeTrue();

    $content = json_decode(file_get_contents($destinationDirectory.'/composer.json'), true);

    expect($content['scripts']['post-install-cmd'])
        ->toBeArray()
        ->toHaveCount(3)
        ->toContain('@php artisan key:generate')
        ->toContain('@php artisan migrate')
        ->toContain('@php artisan db:seed');
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
