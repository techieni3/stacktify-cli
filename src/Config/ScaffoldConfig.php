<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Config;

use Symfony\Component\Console\Input\InputInterface;
use Techieni3\StacktifyCli\Enums\Authentication;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Enums\DeveloperTool;
use Techieni3\StacktifyCli\Enums\Frontend;
use Techieni3\StacktifyCli\Enums\NodePackageManager;
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
     * The selected package manager.
     */
    private NodePackageManager $packageManager;

    /**
     * The selected testing framework.
     */
    private TestingFramework $testingFramework;

    /**
     * Selected tooling preference.
     */
    private ToolingPreference $toolingPreference;

    /**
     * Developer tools chosen when using a custom setup.
     *
     * @var array<DeveloperTool>
     */
    private array $developerTools = [];

    /**
     * Pest plugins to install.
     *
     * @var array<int, PestPlugin>
     */
    private array $pestPlugins = [];

    /**
     * Create a new ScaffoldConfig instance.
     */
    public function __construct(private readonly InputInterface $input) {}

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
    public function setPackageManager(NodePackageManager $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    /**
     * Get the package manager.
     */
    public function getPackageManager(): NodePackageManager
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
    public function setDeveloperTools(array $tools): void
    {
        $this->developerTools = $tools;
    }

    /**
     * Get the developer tools to install.
     *
     * @return array<DeveloperTool>
     */
    public function getDeveloperTools(): array
    {
        return $this->developerTools;
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
     * Determine if the given hostname can be resolved.
     */
    private function canResolveHostname(string $hostname): bool
    {
        return gethostbyname($hostname.'.') !== $hostname.'.';
    }
}
