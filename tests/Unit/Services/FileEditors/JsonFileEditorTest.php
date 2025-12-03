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

it('sets a simple value using dot notation', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('version', '1.0.0');

    expect($composerJson->save())
        ->toBeTrue()
        ->and(file_get_contents($destinationDirectory.'/composer.json'))
        ->toContain('"version": "1.0.0"');
});

it('sets a nested value using dot notation', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('extra.laravel.dont-discover', ['laravel/telescope']);

    expect($composerJson->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/composer.json');

    expect($content)
        ->toContain('"extra"')
        ->and($content)
        ->toContain('"laravel"')
        ->and($content)
        ->toContain('"dont-discover"')
        ->and($content)
        ->toContain('"laravel/telescope"');
});

it('sets deeply nested values', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('config.platform.php', '8.3');

    expect($composerJson->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/composer.json');

    expect($content)
        ->toContain('"config"')
        ->and($content)
        ->toContain('"platform"')
        ->and($content)
        ->toContain('"php": "8.3"');
});

it('gets a value using dot notation', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('extra.laravel.dont-discover', ['laravel/telescope']);
    $composerJson->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $value = $composerJson->get('extra.laravel.dont-discover');

    expect($value)->toBe(['laravel/telescope']);
});

it('gets a value with default when key does not exist', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $value = $composerJson->get('nonexistent.key', 'default');

    expect($value)->toBe('default');
});

it('checks if a key exists using dot notation', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('extra.laravel.dont-discover', ['laravel/telescope']);

    expect($composerJson->has('extra.laravel.dont-discover'))
        ->toBeTrue()
        ->and($composerJson->has('nonexistent.key'))
        ->toBeFalse();
});

it('appends a value to an array using dot notation', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('extra.laravel.dont-discover', ['laravel/telescope']);
    $composerJson->append('extra.laravel.dont-discover', 'laravel/tinker');
    $composerJson->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $value = $composerJson->get('extra.laravel.dont-discover');

    expect($value)->toBe(['laravel/telescope', 'laravel/tinker']);
});

it('appends to a non-existent array', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->append('keywords', 'laravel');
    $composerJson->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $value = $composerJson->get('keywords');

    expect($value)->toBe(['laravel']);
});

it('merges values into an array', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('extra.laravel.dont-discover', ['laravel/telescope']);
    $composerJson->merge('extra.laravel.dont-discover', [
        'laravel/tinker',
        'laravel/dusk',
    ]);
    $composerJson->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $value = $composerJson->get('extra.laravel.dont-discover');

    expect($value)->toBe([
        'laravel/telescope',
        'laravel/tinker',
        'laravel/dusk',
    ]);
});

it('merges into a non-existent array', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->merge('keywords', ['laravel', 'cli']);
    $composerJson->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $value = $composerJson->get('keywords');

    expect($value)->toBe(['laravel', 'cli']);
});

it('removes a value from an array', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('extra.laravel.dont-discover', [
        'laravel/telescope',
        'laravel/tinker',
        'laravel/dusk',
    ]);
    $composerJson->removeValue('extra.laravel.dont-discover', 'laravel/tinker');
    $composerJson->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $value = $composerJson->get('extra.laravel.dont-discover');

    expect($value)->toBe(['laravel/telescope', 'laravel/dusk']);
});

it('does nothing when removing from non-existent array', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->removeValue('nonexistent.key', 'value');

    expect($composerJson->save())->toBeFalse();
});

it('deletes a key using dot notation', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->set('extra.laravel.dont-discover', ['laravel/telescope']);
    $composerJson->set('extra.laravel.aliases', [
        'MyAlias' => 'App\\Facades\\MyFacade',
    ]);

    $composerJson->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');
    $composerJson->delete('extra.laravel.dont-discover');
    $composerJson->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    expect($composerJson->has('extra.laravel.dont-discover'))
        ->toBeFalse()
        ->and($composerJson->has('extra.laravel.aliases'))
        ->toBeTrue();
});

it('does nothing when deleting non-existent key', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson->delete('nonexistent.key');

    expect($composerJson->save())->toBeFalse();
});

it('supports method chaining', function () use ($destinationDirectory): void {
    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    $composerJson
        ->set('version', '2.0.0')
        ->append('keywords', 'laravel')
        ->append('keywords', 'cli')
        ->set('extra.laravel.dont-discover', ['laravel/telescope'])
        ->addScript(new Script('test', 'phpunit'))
        ->save();

    $composerJson = new JsonFileEditor($destinationDirectory.'/composer.json');

    expect($composerJson->get('version'))
        ->toBe('2.0.0')
        ->and($composerJson->get('keywords'))
        ->toBe(['laravel', 'cli'])
        ->and($composerJson->get('extra.laravel.dont-discover'))
        ->toBe(['laravel/telescope'])
        ->and($composerJson->hasScript('test'))
        ->toBeTrue();
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
