<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Config;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Techieni3\StacktifyCli\Enums\Authentication;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Enums\DeveloperTool;
use Techieni3\StacktifyCli\Enums\Frontend;
use Techieni3\StacktifyCli\Enums\PestPlugin;
use Techieni3\StacktifyCli\Enums\TestingFramework;
use Techieni3\StacktifyCli\Enums\ToolingPreference;

final class ScaffoldConfig
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
     * The selected package manager executable name (e.g., "npm", "pnpm", "bun").
     */
    private string $packageManager = 'npm';

    /**
     * The selected testing framework.
     */
    private TestingFramework $testingFramework;

    /**
     * The path to the PHP binary.
     */
    private readonly string $phpBinary;

    /**
     * Selected tooling preference.
     */
    private ToolingPreference $toolingPreference;

    /**
     * Developer tools chosen when using a custom setup.
     *
     * @var array<DeveloperTool>
     */
    private array $devTools = [];

    /**
     * Pest plugins to install.
     *
     * @var array<int, PestPlugin>
     */
    private array $pestPlugins = [];

    /**
     * Create a new ScaffoldConfig instance.
     */
    public function __construct(private readonly InputInterface $input)
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
        return $this->authentication ?? Authentication::None;
    }

    /**
     * Set the package manager.
     */
    public function setPackageManager(string $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    /**
     * Get the package manager.
     */
    public function getPackageManager(): string
    {
        return $this->packageManager;
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
     * Set the tooling preference.
     */
    public function setToolingPreference(ToolingPreference $toolingPreference): void
    {
        $this->toolingPreference = $toolingPreference;
    }

    /**
     * Get the tooling preference.
     */
    public function getToolingPreference(): ToolingPreference
    {
        return $this->toolingPreference;
    }

    /**
     * Set the developer tools to install.
     *
     * @param  array<DeveloperTool>  $tools
     */
    public function setDevTools(array $tools): void
    {
        $this->devTools = $tools;
    }

    /**
     * Get the developer tools to install.
     *
     * @return array<DeveloperTool>
     */
    public function getDevTools(): array
    {
        return $this->devTools;
    }

    /**
     * @param  array<int, PestPlugin>  $plugins
     */
    public function setPestPlugins(array $plugins): void
    {
        $this->pestPlugins = array_values($plugins);
    }

    /**
     * @return array<int, PestPlugin>
     */
    public function getPestPlugins(): array
    {
        return $this->pestPlugins;
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
