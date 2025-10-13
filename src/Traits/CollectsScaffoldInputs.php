<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Traits;

use RuntimeException;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Enums\Authentication;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Enums\Frontend;
use Techieni3\StacktifyCli\Enums\TestingFramework;
use Techieni3\StacktifyCli\Traits\Prompts\GitPrompts;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

trait CollectsScaffoldInputs
{
    use GitPrompts;

    abstract protected function verifyApplicationDoesntExist(string $directory): void;

    private function collectScaffoldInputs(): void
    {
        $this->config = new ScaffoldConfig($this->input);

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
                            $this->verifyApplicationDoesntExist($this->config->getInstallationDirectory($value));
                        } catch (RuntimeException) {
                            return 'Application already exists.';
                        }
                    }
                }
            ));
        }

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

        $this->config->setTestingFramework(
            TestingFramework::from(
                (string) select(
                    label: 'Which testing framework do you prefer?',
                    options: TestingFramework::options(),
                    default: TestingFramework::default(),
                )
            )
        );
    }

    private function reviewAndConfirm(): bool
    {
        $projectPath = $this->config->getInstallationDirectory();

        $this->io->section('Review your selections');

        $this->io->definitionList(
            ['Project name' => $this->config->getAppName()],
            ['Path' => realpath($projectPath) ?: $projectPath],
            ['Frontend stack' => $this->config->getFrontend()->label()],
            ['Authentication' => $this->config->getAuthentication()->label()],
            ['Database' => $this->config->getDatabase()->label()],
            ['Testing framework' => $this->config->getTestingFramework()->label()],
            ['Git' => $this->config->isGitEnabled() ? 'Enabled' : 'Skipped'],
        );

        return $this->io->confirm('Proceed with installation?');
    }
}
