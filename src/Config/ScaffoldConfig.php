<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Config;

use Techieni3\StacktifyCli\Enums\Authentication;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Enums\DeveloperTool;
use Techieni3\StacktifyCli\Enums\Frontend;
use Techieni3\StacktifyCli\Enums\NodePackageManager;
use Techieni3\StacktifyCli\Enums\PestPlugin;
use Techieni3\StacktifyCli\Enums\TestingFramework;
use Techieni3\StacktifyCli\Enums\ToolingPreference;

/**
 * Store the scaffold configuration selections for a new project.
 */
final class ScaffoldConfig
{
    /**
     * The name of the application.
     */
    private string $name;

    /**
     * Version to be used.
     */
    private string $version;

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
    private ToolingPreference $toolingPreference = ToolingPreference::Skip;

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
     * Git enabled status.
     */
    private bool $gitEnabled;

    public function __construct()
    {
        $this->frontend = Frontend::from(Frontend::default());
        $this->database = Database::from(Database::default());
        $this->authentication = Authentication::from(Authentication::default());
        $this->packageManager = NodePackageManager::from(NodePackageManager::default());
        $this->testingFramework = TestingFramework::from(TestingFramework::default());
    }

    /**
     * Set the application name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the application name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the version constraint for the application.
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * Get the version constraint for the application.
     */
    public function getVersion(): string
    {
        return $this->version;
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
     * Set the Pest plugins to install.
     *
     * @param  array<int, PestPlugin>  $plugins
     */
    public function setPestPlugins(array $plugins): void
    {
        $this->pestPlugins = array_values($plugins);
    }

    /**
     * Get the Pest plugins to install.
     *
     * @return array<int, PestPlugin>
     */
    public function getPestPlugins(): array
    {
        return $this->pestPlugins;
    }

    /**
     * Set the Git enabled status.
     */
    public function setGitEnabled(bool $enabled): void
    {
        $this->gitEnabled = $enabled;
    }

    /**
     * Get the Git enabled status.
     */
    public function isGitEnabled(): bool
    {
        return $this->gitEnabled;
    }
}
