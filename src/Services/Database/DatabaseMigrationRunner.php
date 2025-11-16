<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Database;

use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Services\ProcessRunner;

/**
 * Runs database migrations for the application.
 */
final readonly class DatabaseMigrationRunner
{
    /**
     * Create a new database migration runner instance.
     */
    public function __construct(
        private ScaffoldConfig $config,
        private PathResolver $paths,
        private string $phpBinary
    ) {}

    /**
     * Run database migrations if applicable.
     */
    public function run(ProcessRunner $processRunner, bool $isInteractiveMode): void
    {
        if ( ! $this->shouldRunMigrations()) {
            return;
        }

        $commands = [
            mb_trim(sprintf(
                $this->phpBinary.' artisan migrate %s',
                $isInteractiveMode ? '' : '--no-interaction',
            )),
        ];

        $processRunner->runCommands(
            commands: $commands,
            workingPath: $this->paths->getInstallationDirectory(),
            description: 'Running database migrations...'
        );
    }

    /**
     * Determine if migrations should be run for the selected database.
     */
    private function shouldRunMigrations(): bool
    {
        return in_array($this->config->getDatabase(), [Database::MySQL, Database::SQLite], true);
    }
}
