<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Database;

use RuntimeException;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

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
        $envPath = $this->paths->getEnvPath();
        $exampleEnvPath = $this->paths->getEnvExamplePath();

        // Set DB_CONNECTION
        $this->setDatabaseDriver($envPath, $exampleEnvPath);

        if ($this->config->getDatabase() === Database::SQLite) {
            $this->configureSqlite($envPath, $exampleEnvPath);

            return;
        }

        $this->configureNonSqliteDatabase($envPath, $exampleEnvPath);
    }

    /**
     * Set the database driver in .env files.
     */
    private function setDatabaseDriver(string $envPath, string $exampleEnvPath): void
    {
        $replacement = new PregReplacement(
            regex: '/DB_CONNECTION=.*/',
            replace: 'DB_CONNECTION='.$this->config->getDatabase()->driver(),
        );

        FileEditor::pregReplaceInFile($envPath, $replacement);
        FileEditor::pregReplaceInFile($exampleEnvPath, $replacement);
    }

    /**
     * Configure SQLite database settings.
     */
    private function configureSqlite(string $envPath, string $exampleEnvPath): void
    {
        $environment = file_get_contents($envPath);

        if ($environment === false) {
            throw new RuntimeException("Failed to read file: {$envPath}");
        }

        // Comment database options for SQLite if not already commented
        if ( ! str_contains($environment, '# DB_HOST=127.0.0.1')) {
            $this->commentDatabaseConfiguration($envPath, $exampleEnvPath);
        }

        // Create the database.sqlite file if it doesn't exist
        $sqlitePath = $this->paths->getSqliteDatabasePath();
        if ( ! file_exists($sqlitePath)) {
            touch($sqlitePath);
        }
    }

    /**
     * Configure non-SQLite database settings.
     */
    private function configureNonSqliteDatabase(string $envPath, string $exampleEnvPath): void
    {
        // Delete default database.sqlite file if exists
        $sqlitePath = $this->paths->getSqliteDatabasePath();
        if (file_exists($sqlitePath)) {
            @unlink($sqlitePath);
        }

        $envHandler = FileEditor::text($envPath);
        $envExampleHandler = FileEditor::text($exampleEnvPath);

        // Uncomment database configuration options
        $unCommentReplacement = $this->getUncommentDatabaseConfiguration();
        $envHandler->replace($unCommentReplacement);
        $envExampleHandler->replace($unCommentReplacement);

        // Set DB_PORT
        $portReplacement = new Replacement(
            search: 'DB_PORT=3306',
            replace: 'DB_PORT='.$this->config->getDatabase()->defaultPort(),
        );
        $envHandler->replace($portReplacement);
        $envExampleHandler->replace($portReplacement);

        // Set DB_DATABASE
        $dbNameReplacement = new Replacement(
            search: 'DB_DATABASE=laravel',
            replace: 'DB_DATABASE='.str_replace('-', '_', mb_strtolower($this->config->getName())),
        );
        $envHandler->replace($dbNameReplacement);
        $envExampleHandler->replace($dbNameReplacement);

        $envHandler->save();
        $envExampleHandler->save();
    }

    /**
     * Comment the database configuration for SQLite.
     */
    private function commentDatabaseConfiguration(string $envPath, string $exampleEnvPath): void
    {
        $defaults = [
            'DB_HOST=127.0.0.1',
            'DB_PORT=3306',
            'DB_DATABASE=laravel',
            'DB_USERNAME=root',
            'DB_PASSWORD=',
        ];

        $commentedDefaults = collect($defaults)->map(static fn (string $default): string => "# {$default}")->all();

        $commentReplacement = new Replacement(
            search: $defaults,
            replace: $commentedDefaults
        );

        (FileEditor::text($envPath))
            ->replace($commentReplacement)
            ->save();

        (FileEditor::text($exampleEnvPath))
            ->replace($commentReplacement)
            ->save();
    }

    /**
     * Get the replacement for uncommenting the database configuration.
     */
    private function getUncommentDatabaseConfiguration(): Replacement
    {
        $defaults = [
            '# DB_HOST=127.0.0.1',
            '# DB_PORT=3306',
            '# DB_DATABASE=laravel',
            '# DB_USERNAME=root',
            '# DB_PASSWORD=',
        ];

        $uncommentedDefaults = collect($defaults)->map(static fn (string $default): string => mb_substr($default, 2))->all();

        return new Replacement(
            search: $defaults,
            replace: $uncommentedDefaults
        );
    }
}
