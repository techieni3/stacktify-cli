<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Traits;

use RuntimeException;
use Techieni3\StacktifyCli\Enums\Authentication;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Enums\DeveloperTool;
use Techieni3\StacktifyCli\Enums\Frontend;
use Techieni3\StacktifyCli\Enums\NodePackageManager;
use Techieni3\StacktifyCli\Enums\PestPlugin;
use Techieni3\StacktifyCli\Enums\TestingFramework;
use Techieni3\StacktifyCli\Enums\ToolingPreference;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Traits\Prompts\PromptsForGitCredentials;
use Techieni3\StacktifyCli\Traits\Prompts\PromptsForPackageManager;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Gather scaffold configuration input from the user.
 */
trait CollectsScaffoldInputs
{
    use PromptsForGitCredentials;
    use PromptsForPackageManager;

    /**
     * Ensure the application directory does not already exist.
     */
    abstract protected function verifyApplicationDoesntExist(string $directory): void;

    /**
     * Collect all scaffold selections from interactive prompts.
     */
    private function collectScaffoldInputs(): void
    {
        if ( ! $this->input->getArgument('name')) {
            $this->input->setArgument('name', text(
                label: 'What is the name of your project?',
                placeholder: 'E.g. example-app',
                required: 'The project name is required.',
                validate: function ($value) {
                    if (preg_match('/[^\pL\pN\-_.]/u', $value) !== 0) {
                        return 'The name may only contain letters, numbers, dashes, underscores, and periods.';
                    }

                    if ($value === '.' && $this->input->getOption('force')) {
                        return 'Cannot use --force option when using current directory for installation.';
                    }

                    if ($this->input->getOption('force') !== true) {
                        try {
                            $this->verifyApplicationDoesntExist(new PathResolver($value)->getInstallationDirectory());
                        } catch (RuntimeException) {
                            return 'Application already exists.';
                        }
                    }
                }
            ));
        }

        $this->config->setName($this->getNameFromInput());

        $this->config->setFrontend(
            Frontend::from(
                (string) select(
                    label: 'Which frontend stack would you like to use?',
                    options: Frontend::options(),
                    default: Frontend::default(),
                )
            )
        );

        if ($this->config->getFrontend() !== Frontend::Api) {
            $this->config->setPackageManager($this->detectOrAskPackageManager());
        } else {
            $this->config->setPackageManager(NodePackageManager::Npm);
        }

        if ($this->config->getFrontend() !== Frontend::Api) {
            $this->config->setAuthentication(
                Authentication::from(
                    (string) select(
                        label: 'Which authentication provider do you prefer?',
                        options: Authentication::options(),
                        default: Authentication::default(),
                    )
                )
            );
        }

        $this->config->setDatabase(
            Database::from(
                (string) select(
                    label: 'Which database will your application use?',
                    options: Database::options(),
                    default: Database::default(),
                )
            )
        );

        $testingFramework = TestingFramework::from(
            (string) select(
                label: 'Which testing framework do you prefer?',
                options: TestingFramework::options(),
                default: TestingFramework::default(),
            )
        );

        $this->config->setTestingFramework($testingFramework);

        if ($testingFramework === TestingFramework::Pest) {
            $selectedPlugins = multiselect(
                label: 'Which Pest plugins would you like to install?',
                options: PestPlugin::options(),
                default: PestPlugin::default(),
            );

            $this->config->setPestPlugins(PestPlugin::fromSelection($selectedPlugins));
        } else {
            $this->config->setPestPlugins([]);
        }

        $toolingPreference = ToolingPreference::from(
            (string) select(
                label: 'Would you like Stacktify to apply recommended settings and developer tools?',
                options: ToolingPreference::options(),
                default: ToolingPreference::default(),
            )
        );

        $this->config->setToolingPreference($toolingPreference);

        if ($toolingPreference === ToolingPreference::Custom) {
            $selectedTools = multiselect(
                label: 'Select the tools you want to include:',
                options: DeveloperTool::options(),
            );

            $this->config->setDeveloperTools(DeveloperTool::fromSelection($selectedTools));
        } else {
            $this->config->setDeveloperTools([]);
        }

        $this->config->setVersion($this->input->getOption('dev') ? 'dev-master' : '');

        $this->config->setGitEnabled( ! (bool) $this->input->getOption('no-git'));
    }

    /**
     * Configure defaults when the command is run non-interactively.
     */
    private function prepareNonInteractiveConfiguration(): void
    {
        $this->config->setName($this->getNameFromInput());

        $this->config->setVersion($this->input->getOption('dev') ? 'dev-master' : '');

        $this->config->setGitEnabled( ! (bool) $this->input->getOption('no-git'));
    }

    /**
     * Present a summary of selections and ask for confirmation.
     */
    private function reviewAndConfirm(): bool
    {
        if ( ! $this->input->isInteractive()) {
            return true;
        }

        $projectPath = $this->paths->getInstallationDirectory();

        $selections = [
            ['Project name' => $this->config->getName()],
            ['Path' => realpath($projectPath) ?: $projectPath],
            ['Frontend stack' => $this->config->getFrontend()->label()],
        ];

        if ($this->config->getFrontend() !== Frontend::Api) {
            $selections[] = ['Package manager' => $this->config->getPackageManager()->label()];
        }

        $selections[] = ['Authentication provider' => $this->config->getAuthentication()->label()];
        $selections[] = ['Database' => $this->config->getDatabase()->label()];
        $selections[] = ['Testing framework' => $this->config->getTestingFramework()->label()];

        if ($this->config->getTestingFramework() === TestingFramework::Pest) {
            $selections[] = ['Pest plugins' => $this->pestPluginsSummary()];
        }

        $selections[] = ['Tooling setup' => $this->toolingSummary()];
        $selections[] = ['Developer tools' => $this->developerToolsSummary()];
        $selections[] = ['Git' => $this->config->isGitEnabled() ? 'Enabled' : 'Skipped'];

        $this->io->section('Review your selections');
        $this->io->definitionList(...$selections);

        return $this->io->confirm('Proceed with installation?');
    }

    /**
     * Resolve the project name provided via input.
     */
    private function getNameFromInput(): string
    {
        return mb_rtrim((string) $this->input->getArgument('name'), '/\\');
    }

    /**
     * Build a description of the tooling preference selection.
     */
    private function toolingSummary(): string
    {
        return match ($this->config->getToolingPreference()) {
            ToolingPreference::Recommended => 'Recommended settings and tools',
            ToolingPreference::Custom => 'Custom selection',
            ToolingPreference::Skip => 'Installation only',
        };
    }

    /**
     * Build a description of the selected developer tools.
     */
    private function developerToolsSummary(): string
    {
        $tools = $this->config->getDeveloperTools();

        if ($tools === []) {
            return $this->config->getToolingPreference() === ToolingPreference::Custom
                ? 'None selected'
                : 'Managed automatically';
        }

        return implode(', ', array_map(
            static fn (DeveloperTool $tool): string => $tool->label(),
            $tools
        ));
    }

    /**
     * Build a description of the selected Pest plugins.
     */
    private function pestPluginsSummary(): string
    {
        if ($this->config->getTestingFramework() !== TestingFramework::Pest) {
            return 'Not applicable';
        }

        $plugins = $this->config->getPestPlugins();

        if ($plugins === []) {
            return 'None selected';
        }

        return implode(', ', array_map(
            static fn (PestPlugin $plugin): string => $plugin->label(),
            $plugins
        ));
    }
}
