<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Techieni3\StacktifyCli\Services\FileEditors\ConfigFileEditor;

$destinationDirectory = dirname(__DIR__).'/../../Workspace';

beforeEach(function () use ($destinationDirectory): void {
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($destinationDirectory);

    $filesystem->copy(
        dirname(__DIR__).'/../../Fixtures/app.config.php',
        $destinationDirectory.'/app.php'
    );
});

afterEach(function () use ($destinationDirectory): void {
    if (file_exists($destinationDirectory.'/app.php')) {
        unlink($destinationDirectory.'/app.php');
    }
});

describe('set', function () use ($destinationDirectory): void {
    it('sets a simple string value', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('name', 'MyApp')->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'name' => 'MyApp'");
    });

    it('sets a env function as value', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('name', static fn () => env('APP_NAME', 'My App'))->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'name' => env('APP_NAME', 'My App')");
    });

    it('sets nested env function as value', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('connections.sqlite.busy_timeout', static fn () => env('DB_BUSY_TIMEOUT', 5000))
            ->set('connections.sqlite.journal_mode', static fn () => env('DB_JOURNAL_MODE', 'WAL'))
            ->set('connections.sqlite.synchronous', static fn () => env('DB_SYNCHRONOUS', 'NORMAL'))
            ->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'busy_timeout' => env('DB_BUSY_TIMEOUT', 5000)");
    });

    it('sets a env function as value from array', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $configs = [
            'name' => static fn () => env('APP_NAME', 'My App'),
        ];

        foreach ($configs as $name => $value) {
            $editor->set($name, $value);
        }

        $editor->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'name' => env('APP_NAME', 'My App')");
    });

    it('sets multiple values', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('name', 'MyApp')
            ->set('timezone', 'America/New_York')
            ->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'name' => 'MyApp'")
            ->and($content)->toContain("'timezone' => 'America/New_York'");
    });

    it('sets a boolean value', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('debug', true)->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'debug' => true");
    });

    it('sets an integer value', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('timeout', 300)->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'timeout' => 300");
    });

    it('sets a nested value using dot notation', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('database.connections.mysql.strict', true)->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain('database')
            ->and($content)->toContain('connections')
            ->and($content)->toContain('mysql')
            ->and($content)->toContain('strict');
    });
});

describe('append', function () use ($destinationDirectory): void {
    it('appends a value to an existing array', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->append('providers', 'App\Providers\CustomServiceProvider')->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain('App\Providers\CustomServiceProvider');
    });

    it('appends multiple values', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->append('providers', 'App\Providers\CustomServiceProvider')
            ->append('providers', 'App\Providers\AnotherServiceProvider')
            ->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain('App\Providers\CustomServiceProvider')
            ->and($content)->toContain('App\Providers\AnotherServiceProvider');
    });
});

describe('merge', function () use ($destinationDirectory): void {
    it('merges associative array values', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->merge('aliases', [
            'MyFacade' => 'App\Facades\MyFacade',
            'AnotherFacade' => 'App\Facades\AnotherFacade',
        ])->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain('MyFacade')
            ->and($content)->toContain('App\Facades\MyFacade')
            ->and($content)->toContain('AnotherFacade')
            ->and($content)->toContain('App\Facades\AnotherFacade');
    });

    it('merges indexed array values', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->merge('providers', [
            'App\Providers\CustomServiceProvider',
            'App\Providers\AnotherServiceProvider',
        ])->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain('App\Providers\CustomServiceProvider')
            ->and($content)->toContain('App\Providers\AnotherServiceProvider');
    });
});

describe('remove', function () use ($destinationDirectory): void {
    it('removes a key from the config', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->remove('locale')->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->not->toContain("'locale' =>");
    });

    it('removes multiple keys', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->remove('locale')
            ->remove('timezone')
            ->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->not->toContain("'locale' =>")
            ->and($content)->not->toContain("'timezone' =>");
    });
});

describe('fluent interface', function () use ($destinationDirectory): void {
    it('chains multiple operations', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('name', 'MyApp')
            ->set('timezone', 'America/New_York')
            ->append('providers', 'App\Providers\CustomServiceProvider')
            ->merge('aliases', ['MyFacade' => 'App\Facades\MyFacade'])
            ->remove('locale')
            ->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'name' => 'MyApp'")
            ->and($content)->toContain("'timezone' => 'America/New_York'")
            ->and($content)->toContain('App\Providers\CustomServiceProvider')
            ->and($content)->toContain('MyFacade')
            ->and($content)->not->toContain("'locale' =>");
    });
});

describe('change tracking', function () use ($destinationDirectory): void {
    it('only saves when changed', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $result = $editor->save();

        expect($result)->toBeFalse();
    });

    it('saves when value is set', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('name', 'MyApp');

        $result = $editor->save();

        expect($result)->toBeTrue();
    });

    it('saves when value is appended', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->append('providers', 'App\Providers\CustomServiceProvider');

        $result = $editor->save();

        expect($result)->toBeTrue();
    });

    it('saves when values are merged', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->merge('aliases', ['MyFacade' => 'App\Facades\MyFacade']);

        $result = $editor->save();

        expect($result)->toBeTrue();
    });

    it('saves when key is removed', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->remove('locale');

        $result = $editor->save();

        expect($result)->toBeTrue();
    });
});

describe('real-world scenarios', function () use ($destinationDirectory): void {
    it('configures application settings', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->set('name', 'MyApp')
            ->set('env', 'local')
            ->set('debug', true)
            ->set('timezone', 'America/New_York')
            ->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain("'name' => 'MyApp'")
            ->and($content)->toContain("'env' => 'local'")
            ->and($content)->toContain("'debug' => true")
            ->and($content)->toContain("'timezone' => 'America/New_York'");
    });

    it('registers custom service providers and aliases', function () use ($destinationDirectory): void {
        $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

        $editor->append('providers', 'App\Providers\CustomServiceProvider')
            ->append('providers', 'Laravel\Telescope\TelescopeServiceProvider')
            ->merge('aliases', [
                'Telescope' => 'Laravel\Telescope\Telescope',
                'MyFacade' => 'App\Facades\MyFacade',
            ])
            ->save();

        $content = file_get_contents($destinationDirectory.'/app.php');

        expect($content)->toContain('App\Providers\CustomServiceProvider')
            ->and($content)->toContain('Laravel\Telescope\TelescopeServiceProvider')
            ->and($content)->toContain('Telescope')
            ->and($content)->toContain('MyFacade');
    });
});

it('properly write config file', function () use ($destinationDirectory): void {
    $editor = new ConfigFileEditor($destinationDirectory.'/app.php');

    $editor->set('name', 'MyApp')
        ->set('env', 'local')
        ->set('debug', true)
        ->set('timezone', 'America/New_York')
        ->save();

    // Verify the file has valid PHP syntax
    $result = exec(
        command: 'php -l '.escapeshellarg($destinationDirectory.'/app.php').' 2>&1',
        output: $output,
        result_code: $returnCode
    );

    expect($returnCode)->toBe(0)
        ->and($result)->toContain('No syntax errors detected');
});
