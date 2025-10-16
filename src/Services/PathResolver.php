<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

/**
 * Resolves file and directory paths for the project.
 *
 * This class has ONE responsibility: Path resolution and computation.
 */
final readonly class PathResolver
{
    private string $directory;

    public function __construct(private string $projectName)
    {
        $this->directory = $this->getInstallationDirectory();
    }

    /**
     * Get the installation directory for the project.
     */
    public function getInstallationDirectory(): string
    {
        return $this->projectName !== '.' ? getcwd().DIRECTORY_SEPARATOR.$this->projectName : '.';
    }

    /**
     * Get the path to the .env file.
     */
    public function getEnvPath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'.env';
    }

    /**
     * Get the path to the .env.example file.
     */
    public function getEnvExamplePath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'.env.example';
    }

    /**
     * Get the path to the database directory.
     */
    public function getDatabasePath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'database';
    }

    /**
     * Get the path to the SQLite database file.
     */
    public function getSqliteDatabasePath(): string
    {
        return $this->getDatabasePath().DIRECTORY_SEPARATOR.'database.sqlite';
    }

    /**
     * Get the path to a configuration file.
     */
    public function getConfigPath(string $filename): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$filename;
    }
}
