<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

final readonly class DatabaseConfigurator
{
    private string $env;

    private string $exampleEnv;

    public function __construct(
        private ScaffoldConfig $config,
    ) {
        $this->env = $this->config->getEnvFilePath();
        $this->exampleEnv = $this->config->getExampleEnvFilePath();
    }

    public function configureDatabaseConnection(): void
    {
        // DB_CONNECTION
        (FileEditor::init($this->env))
            ->queuePregReplacement(
                new PregReplacement(
                    regex: '/DB_CONNECTION=.*/',
                    replace: 'DB_CONNECTION='.$this->config->getDatabase()->driver(),
                )
            )
            ->applyReplacements();

        (FileEditor::init($this->exampleEnv))
            ->queuePregReplacement(
                new PregReplacement(
                    regex: '/DB_CONNECTION=.*/',
                    replace: 'DB_CONNECTION='.$this->config->getDatabase()->driver(),
                )
            )
            ->applyReplacements();

        if ($this->config->getDatabase() === Database::SQLite) {
            $environment = file_get_contents($this->config->getEnvFilePath());

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

        $envHandler = FileEditor::init($this->env);
        $envExampleHandler = FileEditor::init($this->exampleEnv);

        // Any commented database configuration options should be uncommented when not on SQLite...
        $unCommentReplacement = $this->uncommentDatabaseConfiguration();

        // DB_PORT
        $envHandler->queueReplacement($unCommentReplacement);
        $envExampleHandler->queueReplacement($unCommentReplacement);

        $portReplacement = new Replacement(
            search: 'DB_PORT=3306',
            replace: 'DB_PORT='.$this->config->getDatabase()->defaultPort(),
        );

        $envHandler->queueReplacement($portReplacement);
        $envExampleHandler->queueReplacement($portReplacement);

        // DB_DATABASE
        $dbNameReplacement = new Replacement(
            search: 'DB_DATABASE=laravel',
            replace: 'DB_DATABASE='.str_replace('-', '_', mb_strtolower($this->config->getAppName())),
        );

        $envHandler->queueReplacement($dbNameReplacement);
        $envExampleHandler->queueReplacement($dbNameReplacement);

        $envHandler->applyReplacements();
        $envExampleHandler->applyReplacements();
    }

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

        $processRunner->runCommands($commands, workingPath: $this->config->getInstallationDirectory());
    }

    private function commentDatabaseConfigurationForSqlite(): void
    {
        $defaults = [
            'DB_HOST=127.0.0.1',
            'DB_PORT=3306',
            'DB_DATABASE=laravel',
            'DB_USERNAME=root',
            'DB_PASSWORD=',
        ];

        $commentedDefaults = collect($defaults)->map(static fn ($default) => "# {$default}")->all();

        $commentReplacement = new Replacement(
            search: $defaults,
            replace: $commentedDefaults
        );

        (FileEditor::init($this->env))
            ->queueReplacement($commentReplacement)
            ->applyReplacements();

        (FileEditor::init($this->exampleEnv))
            ->queueReplacement($commentReplacement)
            ->applyReplacements();
    }

    private function uncommentDatabaseConfiguration(): Replacement
    {
        $defaults = [
            '# DB_HOST=127.0.0.1',
            '# DB_PORT=3306',
            '# DB_DATABASE=laravel',
            '# DB_USERNAME=root',
            '# DB_PASSWORD=',
        ];

        $commentedDefaults = collect($defaults)->map(static fn ($default) => mb_substr($default, 2))->all();

        return new Replacement(
            search: $defaults,
            replace: $commentedDefaults
        );
    }
}
