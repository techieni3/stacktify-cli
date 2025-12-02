<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Techieni3\StacktifyCli\Services\FileEditors\EnvFileEditor;

$destinationDirectory = dirname(__DIR__).'/../../Workspace';

beforeEach(function () use ($destinationDirectory): void {
    new Filesystem()->ensureDirectoryExists($destinationDirectory);

    new Filesystem()->copy(
        dirname(__DIR__).'/../../Fixtures/.env.example',
        $destinationDirectory.'/.env'
    );
});

afterEach(function () use ($destinationDirectory): void {
    if (file_exists($destinationDirectory.'/.env')) {
        unlink($destinationDirectory.'/.env');
    }
});

describe('read operations', function () use ($destinationDirectory): void {
    it('checks if key exists', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        expect($env->has('APP_NAME'))->toBeTrue()
            ->and($env->has('NON_EXISTENT'))->toBeFalse();
    });

    it('gets a single value', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        expect($env->get('APP_NAME'))->toBe('Laravel')
            ->and($env->get('NON_EXISTENT'))->toBeNull();
    });

    it('gets all values', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        $all = $env->all();

        expect($all)->toHaveKey('APP_NAME', 'Laravel')
            ->and($all)->toHaveKey('APP_ENV', 'local')
            ->and($all)->toHaveKey('DB_CONNECTION', 'sqlite')
            ->and($all)->toHaveKey('REDIS_PORT', '6379');
    });

    it('unquotes quoted values when reading', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        expect($env->get('MAIL_FROM_ADDRESS'))->toBe('hello@example.com')
            ->and($env->get('MAIL_FROM_NAME'))->toBe('${APP_NAME}');
    });

    it('reads commented keys correctly', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        expect($env->has('DB_HOST'))->toBeTrue()
            ->and($env->get('DB_HOST'))->toBe('127.0.0.1')
            ->and($env->isCommented('DB_HOST'))->toBeTrue();
    });
});

describe('write operations', function () use ($destinationDirectory): void {
    it('sets a single value without quotes (no special chars)', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->set('APP_ENV', 'production')->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('APP_ENV=production')
            ->and($content)->not->toContain('APP_ENV="production"');
    });

    it('sets a single value with auto-quoting (has spaces)', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->set('APP_NAME', 'My Application')->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('APP_NAME="My Application"');
    });

    it('sets multiple values with array', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->set([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'my app db',
        ])->save();

        $content = file_get_contents($destinationDirectory.'/.env');
        $freshEnv = new EnvFileEditor($destinationDirectory.'/.env');

        expect($content)->toContain('DB_CONNECTION=mysql')
            ->and($content)->toContain('DB_HOST=127.0.0.1')
            ->and($content)->toContain('DB_PORT=3306')
            ->and($content)->toContain('DB_DATABASE="my app db"')
            ->and($freshEnv->isCommented('DB_DATABASE'))->toBeFalse();
    });

    it('updates existing value', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        expect($env->get('APP_ENV'))->toBe('local');

        $env->set('APP_ENV', 'production')->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('APP_ENV=production')
            ->and($content)->not->toContain('APP_ENV=local');
    });

    it('adds a new key', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->set('NEW_KEY', 'new_value')->save();

        expect($env->get('NEW_KEY'))->toBe('new_value');

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('NEW_KEY=new_value');
    });
});

describe('helper methods', function () use ($destinationDirectory): void {
    it('sets quoted value forcefully', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->setQuoted('APP_ENV', 'production')->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('APP_ENV="production"');
    });

    it('sets boolean value as true', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->setBoolean('APP_DEBUG', true)->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('APP_DEBUG=true')
            ->and($content)->not->toContain('APP_DEBUG="true"');
    });

    it('sets boolean value as false', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->setBoolean('APP_DEBUG', false)->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('APP_DEBUG=false')
            ->and($content)->not->toContain('APP_DEBUG="false"');
    });
});

describe('comment operations', function () use ($destinationDirectory): void {
    it('comments a single key', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        expect($env->isCommented('APP_ENV'))->toBeFalse();

        $env->comment('APP_ENV')->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('# APP_ENV=local')
            ->and($env->isCommented('APP_ENV'))->toBeTrue();
    });

    it('comments multiple keys', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->comment(['APP_NAME', 'APP_ENV', 'APP_DEBUG'])->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('# APP_NAME=Laravel')
            ->and($content)->toContain('# APP_ENV=local')
            ->and($content)->toContain('# APP_DEBUG=true');
    });

    it('uncomments a single key', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        expect($env->isCommented('DB_HOST'))->toBeTrue();

        $env->uncomment('DB_HOST')->save();

        $freshEnv = new EnvFileEditor($destinationDirectory.'/.env');
        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('DB_HOST=127.0.0.1')
            ->and($content)->not->toContain('# DB_HOST=127.0.0.1')
            ->and($freshEnv->isCommented('DB_HOST'))->toBeFalse();
    });

    it('uncomments multiple keys', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->uncomment(['DB_HOST', 'DB_PORT', 'DB_DATABASE'])->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('DB_HOST=127.0.0.1')
            ->and($content)->toContain('DB_PORT=3306')
            ->and($content)->toContain('DB_DATABASE=laravel')
            ->and($content)->not->toContain('# DB_HOST')
            ->and($content)->not->toContain('# DB_PORT')
            ->and($content)->not->toContain('# DB_DATABASE');
    });
});

describe('file preservation', function () use ($destinationDirectory): void {
    it('preserves file comments', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->set('APP_DEBUG', 'false')->save();

        $result = file_get_contents($destinationDirectory.'/.env');

        expect($result)->toContain('# APP_MAINTENANCE_STORE=database')
            ->and($result)->toContain('# PHP_CLI_SERVER_WORKERS=4');
    });
});

describe('change tracking', function () use ($destinationDirectory): void {
    it('only saves when changed', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $result = $env->save();

        expect($result)->toBeFalse();
    });

    it('saves when value is set', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');
        $env->set('APP_ENV', 'production');

        $result = $env->save();

        expect($result)->toBeTrue();
    });
});

describe('real-world scenarios', function () use ($destinationDirectory): void {
    it('configures SQLite database', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        $env->set('DB_CONNECTION', 'sqlite')
            ->comment(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])
            ->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('DB_CONNECTION=sqlite')
            ->and($content)->toContain('# DB_HOST=127.0.0.1')
            ->and($content)->toContain('# DB_PORT=3306')
            ->and($content)->toContain('# DB_DATABASE=laravel');
    });

    it('switches from SQLite to MySQL', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        $env->set([
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'my_app',
        ])
            ->uncomment(['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'])
            ->save();

        $content = file_get_contents($destinationDirectory.'/.env');

        expect($content)->toContain('DB_CONNECTION=mysql')
            ->and($content)->toContain('DB_HOST=127.0.0.1')
            ->and($content)->not->toContain('# DB_HOST');
    });

    it('updates mail configuration', function () use ($destinationDirectory): void {
        $env = new EnvFileEditor($destinationDirectory.'/.env');

        $env->set([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.mailtrap.io',
            'MAIL_PORT' => '2525',
            'MAIL_USERNAME' => 'testuser',
            'MAIL_PASSWORD' => 'test#pass',
        ])->save();

        expect($env->get('MAIL_MAILER'))->toBe('smtp')
            ->and($env->get('MAIL_HOST'))->toBe('smtp.mailtrap.io')
            ->and($env->get('MAIL_PASSWORD'))->toBe('test#pass');
    });
});

it('properly writes .env file', function () use ($destinationDirectory): void {
    $env = new EnvFileEditor($destinationDirectory.'/.env');

    $env->set('APP_NAME', 'My App')
        ->set('DB_DATABASE', 'stacktify')
        ->set('DB_PASSWORD', 's3cr3t p@ss')
        ->setBoolean('APP_DEBUG', true)
        ->comment('DB_PASSWORD')
        ->uncomment('DB_PASSWORD')
        ->save();

    $content = file_get_contents($destinationDirectory.'/.env');
    $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];

    foreach ($lines as $line) {
        $trim = mb_trim($line);
        if ($trim === '') {
            continue;
        }

        if (str_starts_with($trim, '#')) {
            continue;
        }

        // Basic .env syntax check: KEY=VALUE format
        expect((bool) preg_match('/^[A-Z0-9_]+=.*/', $trim))->toBeTrue();
    }

    // Spot-check quoting rules and loaded values using the editor
    expect($content)->toContain('APP_NAME="My App"')
        ->and($content)->toContain('APP_DEBUG=true');

    $fresh = new EnvFileEditor($destinationDirectory.'/.env');
    expect($fresh->get('APP_NAME'))->toBe('My App')
        ->and($fresh->get('APP_DEBUG'))->toBe('true')
        ->and($fresh->isCommented('DB_PASSWORD'))->toBeFalse();
});
