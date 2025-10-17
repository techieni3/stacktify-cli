<?php

namespace Techieni3\StacktifyCli\Services\DeveloperTools;

use Techieni3\StacktifyCli\Contracts\Installable;
use Techieni3\StacktifyCli\Enums\DeveloperTool;
use Techieni3\StacktifyCli\Services\Composer;
use Techieni3\StacktifyCli\Services\FileEditor;
use Techieni3\StacktifyCli\Services\ProcessRunner;

final readonly class DeveloperToolsInstaller
{
    public function __construct(
        private ProcessRunner $process,
        private Composer $composer,
        private string $projectPath
    ) {}

    /**
     * Install selected developer tools.
     *
     * @param array<DeveloperTool> $tools
     */
    public function install(array $tools): void
    {
        if (empty($tools)) {
            return;
        }

        $installables = collect($tools)
            ->map(fn(DeveloperTool $tool) => $tool->installable());

        // Phase 1: Collect all package names
        $dependencies = $installables
            ->flatMap(fn(Installable $installable) => $installable->dependencies())
            ->unique()
            ->values()
            ->all();

        $devDependencies = $installables
            ->flatMap(fn(Installable $installable) => $installable->devDependencies())
            ->unique()
            ->values()
            ->all();

        // Phase 2: Install all packages at once
        if (!empty($dependencies)) {
            $this->composer->installDependencies($dependencies);
        }

        if (!empty($devDependencies)) {
            $this->composer->installDevDependencies($devDependencies);
        }

        // Phase 3: Publish stubs and configs
        foreach ($installables as $installable) {
            if (empty($stubs = $installable->stubs())) {
                continue;
            }
            $this->publishStubs($stubs);
        }
    }

    /**
     * Publish stub files to the project.
     */
    private function publishStubs(array $stubs): void
    {

        foreach ($stubs as $source => $destination) {
            $destinationPath = $this->projectPath . DIRECTORY_SEPARATOR . $destination;

           FileEditor::copyFile($source, $destinationPath);
        }
    }
}
