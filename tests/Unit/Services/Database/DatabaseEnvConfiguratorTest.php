<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Services\Database\DatabaseEnvConfigurator;
use Techieni3\StacktifyCli\Services\FileEditors\EnvFileEditor;
use Techieni3\StacktifyCli\Services\PathResolver;

$testProjectName = 'test-db-config-'.uniqid('', true);

beforeEach(function () use ($testProjectName): void {
    $filesystem = new Filesystem();
    $testDir = getcwd().DIRECTORY_SEPARATOR.$testProjectName;
    $fixtureFile = dirname(__DIR__).'/../../Fixtures/.env.example';

    $filesystem->ensureDirectoryExists($testDir);
    $filesystem->ensureDirectoryExists($testDir.'/database');

    // Copy fixture for both .env and .env.example
    $filesystem->copy($fixtureFile, $testDir.'/.env');
    $filesystem->copy($fixtureFile, $testDir.'/.env.example');
});

afterEach(function () use ($testProjectName): void {
    $testDir = getcwd().DIRECTORY_SEPARATOR.$testProjectName;
    $filesystem = new Filesystem();

    if (is_dir($testDir)) {
        $filesystem->deleteDirectory($testDir);
    }
});

describe('SQLite configuration', function () use ($testProjectName): void {
    it('sets DB_CONNECTION to sqlite', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::SQLite);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor($paths->getEnvPath());

        expect($env->get('DB_CONNECTION'))->toBe('sqlite');
    });

    it('comments out MySQL-specific settings in .env', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::SQLite);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $testDir = getcwd().DIRECTORY_SEPARATOR.$testProjectName;
        $env = new EnvFileEditor($testDir.'/.env');

        expect($env->isCommented('DB_HOST'))->toBeTrue()
            ->and($env->isCommented('DB_PORT'))->toBeTrue()
            ->and($env->isCommented('DB_DATABASE'))->toBeTrue()
            ->and($env->isCommented('DB_USERNAME'))->toBeTrue()
            ->and($env->isCommented('DB_PASSWORD'))->toBeTrue();
    });

    it('comments out MySQL-specific settings in .env.example', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::SQLite);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $testDir = getcwd().DIRECTORY_SEPARATOR.$testProjectName;
        $env = new EnvFileEditor($testDir.'/.env.example');

        expect($env->isCommented('DB_HOST'))->toBeTrue()
            ->and($env->isCommented('DB_PORT'))->toBeTrue()
            ->and($env->isCommented('DB_DATABASE'))->toBeTrue()
            ->and($env->isCommented('DB_USERNAME'))->toBeTrue()
            ->and($env->isCommented('DB_PASSWORD'))->toBeTrue();
    });

    it('creates database.sqlite file', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::SQLite);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $testDir = getcwd().DIRECTORY_SEPARATOR.$testProjectName;

        expect(file_exists($testDir.'/database/database.sqlite'))->toBeTrue();
    });

    it('does not create database.sqlite if it already exists', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::SQLite);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);

        $testDir = getcwd().DIRECTORY_SEPARATOR.$testProjectName;

        // Create the file first
        touch($testDir.'/database/database.sqlite');

        $originalTime = filemtime($testDir.'/database/database.sqlite');

        sleep(1);

        $configurator = new DatabaseEnvConfigurator($config, $paths);
        $configurator->configure();

        $newTime = filemtime($testDir.'/database/database.sqlite');

        expect($originalTime)->toBe($newTime);
    });
});

describe('MySQL configuration', function () use ($testProjectName): void {
    it('sets DB_CONNECTION to mysql', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::MySQL);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');

        expect($env->get('DB_CONNECTION'))->toBe('mysql');
    });

    it('sets DB_PORT to 3306', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::MySQL);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');

        expect($env->get('DB_PORT'))->toBe('3306');
    });

    it('sets DB_DATABASE to normalized app name', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::MySQL);
        $config->setName('My-Test-App');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');

        expect($env->get('DB_DATABASE'))->toBe('my_test_app');
    });

    it('uncomments MySQL-specific settings', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::MySQL);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');

        expect($env->isCommented('DB_HOST'))->toBeFalse()
            ->and($env->isCommented('DB_PORT'))->toBeFalse()
            ->and($env->isCommented('DB_DATABASE'))->toBeFalse()
            ->and($env->isCommented('DB_USERNAME'))->toBeFalse()
            ->and($env->isCommented('DB_PASSWORD'))->toBeFalse();
    });

    it('removes database.sqlite if it exists', function () use ($testProjectName): void {
        // Create the SQLite file first
        touch(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/database/database.sqlite');
        expect(file_exists(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/database/database.sqlite'))->toBeTrue();

        $config = new ScaffoldConfig();
        $config->setDatabase(Database::MySQL);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        expect(file_exists(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/database/database.sqlite'))->toBeFalse();
    });

    it('applies configuration to both .env and .env.example', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::MySQL);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');
        $envExample = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env.example');

        expect($env->get('DB_CONNECTION'))->toBe('mysql')
            ->and($envExample->get('DB_CONNECTION'))->toBe('mysql')
            ->and($env->get('DB_PORT'))->toBe('3306')
            ->and($envExample->get('DB_PORT'))->toBe('3306')
            ->and($env->get('DB_DATABASE'))->toBe('test_app')
            ->and($envExample->get('DB_DATABASE'))->toBe('test_app');

    });
});

describe('PostgreSQL configuration', function () use ($testProjectName): void {
    it('sets DB_CONNECTION to pgsql', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::PostgreSQL);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');

        expect($env->get('DB_CONNECTION'))->toBe('pgsql');
    });

    it('sets DB_PORT to 5432', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::PostgreSQL);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');

        expect($env->get('DB_PORT'))->toBe('5432');
    });

    it('uncomments PostgreSQL-specific settings', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::PostgreSQL);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');

        expect($env->isCommented('DB_HOST'))->toBeFalse()
            ->and($env->isCommented('DB_PORT'))->toBeFalse()
            ->and($env->isCommented('DB_DATABASE'))->toBeFalse()
            ->and($env->isCommented('DB_USERNAME'))->toBeFalse()
            ->and($env->isCommented('DB_PASSWORD'))->toBeFalse();
    });
});

describe('MariaDB configuration', function () use ($testProjectName): void {
    it('sets DB_CONNECTION to mariadb', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::MariaDB);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');
        expect($env->get('DB_CONNECTION'))->toBe('mariadb');
    });

    it('sets DB_PORT to 3306', function () use ($testProjectName): void {
        $config = new ScaffoldConfig();
        $config->setDatabase(Database::MariaDB);
        $config->setName('test-app');

        $paths = new PathResolver($testProjectName);
        $configurator = new DatabaseEnvConfigurator($config, $paths);

        $configurator->configure();

        $env = new EnvFileEditor(getcwd().DIRECTORY_SEPARATOR.$testProjectName.'/.env');
        expect($env->get('DB_PORT'))->toBe('3306');
    });
});
