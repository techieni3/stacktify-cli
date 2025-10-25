<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Techieni3\StacktifyCli\Services\FileEditors\JsonFileEditor;

$destinationDirectory = dirname(__DIR__).'/../../Workspace';

beforeEach(function () use ($destinationDirectory): void {
    // check workspace directory existence
    new Filesystem()->ensureDirectoryExists($destinationDirectory);

    new Filesystem()->copy(dirname(__DIR__).'/../../Fixtures/composer.json', $destinationDirectory.'/composer.json');
});

afterEach(function () use ($destinationDirectory): void {
    unlink($destinationDirectory.'/composer.json');
});

it('returns false when saving without changes', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    expect($composerJson->save())->toBeFalse();
});

it('adds a new script', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->addScript('test', 'php artisan test');

    expect($composerJson->save())->toBeTrue()
        ->and(file_get_contents($destinationDirectory.'/composer.json'))->toContain('"test": "php artisan test"');

});
