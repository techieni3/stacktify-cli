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
use Techieni3\StacktifyCli\Services\ApplicationValidator;
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
                            $validator = new ApplicationValidator();
                            $validator->ensureApplicationDoesNotExist(new PathResolver($value)->getInstallationDirectory());
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
            $pestPluginOptions = PestPlugin::options();

            // Remove browser testing plugin for API-only applications
            // API applications don't have a frontend to test with browser automation
            if ($this->config->getFrontend() === Frontend::Api) {
                unset($pestPluginOptions[PestPlugin::BrowserTest->value]);
            }

            // Filter default plugins to only include those still available after removal
            // array_values() is used to re-index the array for proper default selection
            $defaultPestPlugins = array_values(
                array_filter(PestPlugin::default(), static fn (string $plugin): bool => array_key_exists($plugin, $pestPluginOptions))
            );

            $selectedPlugins = multiselect(
                label: 'Which Pest plugins would you like to install?',
                options: $pestPluginOptions,
                default: $defaultPestPlugins,
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
                default: DeveloperTool::recommended()
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
     * Resolve the project name provided via input.
     */
    private function getNameFromInput(): string
    {
        return mb_rtrim((string) $this->input->getArgument('name'), '/\\');
    }
}
