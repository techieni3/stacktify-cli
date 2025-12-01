<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Database;

use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\PathResolver;

/**
 * Configures database connection settings in .env files.
 */
final readonly class DatabaseEnvConfigurator
{
    /**
     * Create a new database environment configurator instance.
     */
    public function __construct(
        private ScaffoldConfig $config,
        private PathResolver $paths
    ) {}

    /**
     * Configure the database connection in .env and .env.example files.
     */
    public function configure(): void
    {
        if ($this->config->getDatabase() === Database::SQLite) {
            $this->configureSqlite();

            return;
        }

        $this->configureNonSqliteDatabase();
    }

    /**
     * Configure SQLite database settings.
     */
    private function configureSqlite(): void
    {
        // Set DB_CONNECTION and comment other database options
        FileEditor::env($this->paths->getEnvPath())
            ->set('DB_CONNECTION', Database::SQLite->driver())
            ->comment(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])
            ->save();

        FileEditor::env($this->paths->getEnvExamplePath())
            ->set('DB_CONNECTION', Database::SQLite->driver())
            ->comment(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])
            ->save();

        // Create the database.sqlite file if it doesn't exist
        $sqlitePath = $this->paths->getSqliteDatabasePath();

        if ( ! file_exists($sqlitePath)) {
            touch($sqlitePath);
        }
    }

    /**
     * Configure non-SQLite database settings.
     */
    private function configureNonSqliteDatabase(): void
    {
        // Delete default database.sqlite file if exists
        $sqlitePath = $this->paths->getSqliteDatabasePath();

        if (file_exists($sqlitePath)) {
            @unlink($sqlitePath);
        }

        $databaseName = str_replace('-', '_', mb_strtolower($this->config->getName()));

        // Set database configuration and uncomment options
        FileEditor::env($this->paths->getEnvPath())
            ->set([
                'DB_CONNECTION' => $this->config->getDatabase()->driver(),
                'DB_PORT' => (string) $this->config->getDatabase()->defaultPort(),
                'DB_DATABASE' => $databaseName,
            ])
            ->uncomment(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])
            ->save();

        FileEditor::env($this->paths->getEnvExamplePath())
            ->set([
                'DB_CONNECTION' => $this->config->getDatabase()->driver(),
                'DB_PORT' => (string) $this->config->getDatabase()->defaultPort(),
                'DB_DATABASE' => $databaseName,
            ])
            ->uncomment(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])
            ->save();
    }
}
