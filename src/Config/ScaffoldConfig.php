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
    public string $name;

    private Frontend $frontend;

    private Database $database;

    private Authentication $authentication;

    private TestingFramework $testingFramework;

    private string $phpBinary;

    public function __construct(
        private InputInterface $input,
    ) {
        $this->phpBinary = new PhpExecutableFinder()->find(false) ?: 'php';
    }

    public function isInteractiveMode(): bool
    {
        return $this->input->isInteractive();
    }

    public function getVersion(): string
    {
        if ($this->input->getOption('dev')) {
            return 'dev-master';
        }

        return '';
    }

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

    public function getPhpBinary(): string
    {
        return $this->phpBinary;
    }

    public function getAppUrl(): string
    {
        $hostname = mb_strtolower($this->getAppName()).'.test';

        return $this->canResolveHostname($hostname)
            ? 'http://'.$hostname
            : 'http://localhost';
    }

    public function setFrontend(Frontend $frontend): void
    {
        $this->frontend = $frontend;
    }

    public function getFrontend(): Frontend
    {
        return $this->frontend;
    }

    public function setAuthentication(Authentication $authentication): void
    {
        $this->authentication = $authentication;
    }

    public function getAuthentication(): Authentication
    {
        return $this->authentication;
    }

    public function setDatabase(Database $database): void
    {
        $this->database = $database;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function setTestingFramework(TestingFramework $testingFramework): void
    {
        $this->testingFramework = $testingFramework;
    }

    public function getTestingFramework(): TestingFramework
    {
        return $this->testingFramework;
    }

    public function isGitEnabled(): bool
    {
        return ! (bool) $this->input->getOption('no-git');
    }

    public function getEnvFilePath(): string
    {
        return $this->getInstallationDirectory().'/.env';
    }

    public function getExampleEnvFilePath(): string
    {
        return $this->getInstallationDirectory().'/.env.example';
    }

    private function canResolveHostname(string $hostname): bool
    {
        return gethostbyname($hostname.'.') !== $hostname.'.';
    }
}
