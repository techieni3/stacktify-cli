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
});

afterEach(function () use ($destinationDirectory): void {
    if (file_exists($destinationDirectory.'/composer.json')) {
        unlink($destinationDirectory.'/composer.json');
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
