<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Config;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Techieni3\StacktifyCli\Enums\Authentication;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Enums\Frontend;
use Techieni3\StacktifyCli\Enums\TestingFramework;

final readonly class ScaffoldConfig
{
    /**
     * The name of the application.
     */
    public string $name;

    /**
     * The selected frontend framework.
     */
    private Frontend $frontend;

    /**
     * The selected database.
     */
    private Database $database;

    /**
     * The selected authentication scaffolding.
     */
    private Authentication $authentication;

    /**
     * The selected testing framework.
     */
    private TestingFramework $testingFramework;

    /**
     * The path to the PHP binary.
     */
    private string $phpBinary;

    /**
     * Create a new ScaffoldConfig instance.
     */
    public function __construct(private InputInterface $input)
    {
        $this->phpBinary = new PhpExecutableFinder()->find(false) ?: 'php';
    }

    /**
     * Determine if the command is running in interactive mode.
     */
    public function isInteractiveMode(): bool
    {
        return $this->input->isInteractive();
    }

    /**
     * Get the version constraint for the application.
     */
    public function getVersion(): string
    {
        if ($this->input->getOption('dev')) {
            return 'dev-master';
        }

        return '';
    }

    /**
     * Get the application name.
     */
    public function getAppName(): string
    {
        return mb_rtrim($this->input->getArgument('name'), '/\\');
    }

    /**
     * Get the installation directory for the application.
     */
    public function getInstallationDirectory(?string $name = null): string
    {
        $appName = $name !== null ? mb_rtrim($name, '/\\') : $this->getAppName();

        return $appName !== '.' ? getcwd().'/'.$appName : '.';
    }

    /**
     * Get the path to the PHP binary.
     */
    public function getPhpBinary(): string
    {
        return $this->phpBinary;
    }

    /**
     * Get the application URL.
     */
    public function getAppUrl(): string
    {
        $hostname = mb_strtolower($this->getAppName()).'.test';

        return $this->canResolveHostname($hostname)
            ? 'http://'.$hostname
            : 'http://localhost';
    }

    /**
     * Set the frontend framework.
     */
    public function setFrontend(Frontend $frontend): void
    {
        $this->frontend = $frontend;
    }

    /**
     * Get the frontend framework.
     */
    public function getFrontend(): Frontend
    {
        return $this->frontend;
    }

    /**
     * Set the authentication scaffolding.
     */
    public function setAuthentication(Authentication $authentication): void
    {
        $this->authentication = $authentication;
    }

    /**
     * Get the authentication scaffolding.
     */
    public function getAuthentication(): Authentication
    {
        return $this->authentication;
    }

    /**
     * Set the database.
     */
    public function setDatabase(Database $database): void
    {
        $this->database = $database;
    }

    /**
     * Get the database.
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Set the testing framework.
     */
    public function setTestingFramework(TestingFramework $testingFramework): void
    {
        $this->testingFramework = $testingFramework;
    }

    /**
     * Get the testing framework.
     */
    public function getTestingFramework(): TestingFramework
    {
        return $this->testingFramework;
    }

    /**
     * Determine if Git is enabled.
     */
    public function isGitEnabled(): bool
    {
        return ! (bool) $this->input->getOption('no-git');
    }

    /**
     * Get the path to the .env file.
     */
    public function getEnvFilePath(): string
    {
        return $this->getInstallationDirectory().'/.env';
    }

    /**
     * Get the path to the .env.example file.
     */
    public function getExampleEnvFilePath(): string
    {
        return $this->getInstallationDirectory().'/.env.example';
    }

    /**
     * Determine if the given hostname can be resolved.
     */
    private function canResolveHostname(string $hostname): bool
    {
        return gethostbyname($hostname.'.') !== $hostname.'.';
    }
}
