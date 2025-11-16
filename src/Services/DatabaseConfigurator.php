<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Services\Database\DatabaseEnvConfigurator;
use Techieni3\StacktifyCli\Services\Database\DatabaseMigrationRunner;

/**
 * Orchestrates database configuration and migrations.
 *
 * This class serves as a facade that delegates to specialized services:
 * - DatabaseEnvConfigurator: handles .env file modifications
 * - DatabaseMigrationRunner: executes database migrations
 */
final readonly class DatabaseConfigurator
{
    /**
     * The environment configurator instance.
     */
    private DatabaseEnvConfigurator $envConfigurator;

    /**
     * The migration runner instance.
     */
    private DatabaseMigrationRunner $migrationRunner;

    /**
     * Create a new database configurator instance.
     */
    public function __construct(
        ScaffoldConfig $config,
        PathResolver $paths,
        string $phpBinary
    ) {
        $this->envConfigurator = new DatabaseEnvConfigurator($config, $paths);
        $this->migrationRunner = new DatabaseMigrationRunner($config, $paths, $phpBinary);
    }

    /**
     * Configure the database connection in .env files.
     */
    public function configureDatabaseConnection(): void
    {
        $this->envConfigurator->configure();
    }

    /**
     * Run database migrations.
     */
    public function runMigration(ProcessRunner $processRunner, bool $isInteractiveMode): void
    {
        $this->migrationRunner->run($processRunner, $isInteractiveMode);
    }
}
