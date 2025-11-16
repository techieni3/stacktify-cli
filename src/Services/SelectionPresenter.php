<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Enums\DeveloperTool;
use Techieni3\StacktifyCli\Enums\Frontend;
use Techieni3\StacktifyCli\Enums\PestPlugin;
use Techieni3\StacktifyCli\Enums\TestingFramework;
use Techieni3\StacktifyCli\Enums\ToolingPreference;

/**
 * Presents scaffold configuration selections for user review.
 */
final readonly class SelectionPresenter
{
    /**
     * Create a new selection presenter instance.
     */
    public function __construct(
        private ScaffoldConfig $config,
        private PathResolver $paths
    ) {}

    /**
     * Present a summary of selections and ask for confirmation.
     */
    public function reviewAndConfirm(InputInterface $input, SymfonyStyle $io): bool
    {
        if ( ! $input->isInteractive()) {
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
            $selections[] = ['Pest plugins' => $this->buildPestPluginsSummary()];
        }

        $selections[] = ['Tooling setup' => $this->buildToolingSummary()];
        $selections[] = ['Developer tools' => $this->buildDeveloperToolsSummary()];
        $selections[] = ['Git' => $this->config->isGitEnabled() ? 'Enabled' : 'Skipped'];

        $io->section('Review your selections');
        $io->definitionList(...$selections);

        return $io->confirm('Proceed with installation?');
    }

    /**
     * Build a description of the tooling preference selection.
     */
    private function buildToolingSummary(): string
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
    private function buildDeveloperToolsSummary(): string
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
    private function buildPestPluginsSummary(): string
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
