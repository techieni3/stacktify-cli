<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use RuntimeException;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

/**
 * Configures the database for the application.
 */
final readonly class DatabaseConfigurator
{
    /**
     * The path to the .env file.
     */
    private string $env;

    /**
     * The path to the .env.example file.
     */
    private string $exampleEnv;

    /**
     * Create a new database configurator instance.
     */
    public function __construct(
        private ScaffoldConfig $config,
    ) {
        $this->env = $this->config->getEnvFilePath();
        $this->exampleEnv = $this->config->getExampleEnvFilePath();
    }

    /**
     * Configure the database connection.
     */
    public function configureDatabaseConnection(): void
    {
        // DB_CONNECTION
        FileEditor::pregReplaceInFile(
            filePath: $this->env,
            replacement: new PregReplacement(
                regex: '/DB_CONNECTION=.*/',
                replace: 'DB_CONNECTION='.$this->config->getDatabase()->driver(),
            )
        );

        FileEditor::pregReplaceInFile(
            filePath: $this->exampleEnv,
            replacement: new PregReplacement(
                regex: '/DB_CONNECTION=.*/',
                replace: 'DB_CONNECTION='.$this->config->getDatabase()->driver(),
            )
        );

        if ($this->config->getDatabase() === Database::SQLite) {
            $environment = file_get_contents($this->config->getEnvFilePath());

            if ($environment === false) {
                throw new RuntimeException("Failed to read file: {$this->config->getEnvFilePath()}");
            }

            // If database options aren't commented, comment them for SQLite...
            if ( ! str_contains($environment, '# DB_HOST=127.0.0.1')) {
                $this->commentDatabaseConfigurationForSqlite();
            }

            // create database.sqlite file if doesn't exist
            if ( ! file_exists($this->config->getInstallationDirectory().'/database/database.sqlite')) {
                touch($this->config->getInstallationDirectory().'/database/database.sqlite');
            }

            return;
        }

        // delete default database.sqlite file if exists
        if (file_exists($this->config->getInstallationDirectory().'/database/database.sqlite')) {
            @unlink($this->config->getInstallationDirectory().'/database/database.sqlite');
        }

        $envHandler = FileEditor::open($this->env);
        $envExampleHandler = FileEditor::open($this->exampleEnv);

        // Any commented database configuration options should be uncommented when not on SQLite...
        $unCommentReplacement = $this->uncommentDatabaseConfiguration();

        // DB_PORT
        $envHandler->replace($unCommentReplacement);
        $envExampleHandler->replace($unCommentReplacement);

        $portReplacement = new Replacement(
            search: 'DB_PORT=3306',
            replace: 'DB_PORT='.$this->config->getDatabase()->defaultPort(),
        );

        $envHandler->replace($portReplacement);
        $envExampleHandler->replace($portReplacement);

        // DB_DATABASE
        $dbNameReplacement = new Replacement(
            search: 'DB_DATABASE=laravel',
            replace: 'DB_DATABASE='.str_replace('-', '_', mb_strtolower($this->config->getAppName())),
        );

        $envHandler->replace($dbNameReplacement);
        $envExampleHandler->replace($dbNameReplacement);

        $envHandler->save();
        $envExampleHandler->save();
    }

    /**
     * Run the database migrations.
     */
    public function runMigration(ProcessRunner $processRunner): void
    {
        if ( ! in_array($this->config->getDatabase(), [Database::MySQL, Database::SQLite], true)) {
            return;
        }

        $commands = [
            mb_trim(sprintf(
                $this->config->getPhpBinary().' artisan migrate %s',
                $this->config->isInteractiveMode() ? '' : '--no-interaction',
            )),
        ];

        $processRunner->runCommands(
            commands: $commands,
            workingPath: $this->config->getInstallationDirectory(),
            description: 'Running database migrations...'
        );
    }

    /**
     * Comment the database configuration for SQLite.
     */
    private function commentDatabaseConfigurationForSqlite(): void
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

        (FileEditor::open($this->env))
            ->replace($commentReplacement)
            ->save();

        (FileEditor::open($this->exampleEnv))
            ->replace($commentReplacement)
            ->save();
    }

    /**
     * Get the replacement for uncommenting the database configuration.
     */
    private function uncommentDatabaseConfiguration(): Replacement
    {
        $defaults = [
            '# DB_HOST=127.0.0.1',
            '# DB_PORT=3306',
            '# DB_DATABASE=laravel',
            '# DB_USERNAME=root',
            '# DB_PASSWORD=',
        ];

        $commentedDefaults = collect($defaults)->map(static fn (string $default): string => mb_substr($default, 2))->all();

        return new Replacement(
            search: $defaults,
            replace: $commentedDefaults
        );
    }
}
