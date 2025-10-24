<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

use Techieni3\StacktifyCli\Contracts\Installable;
use Techieni3\StacktifyCli\Enums\DeveloperTool;

final readonly class DeveloperToolsInstaller2
{
    public function __construct(
        private Composer $composer,
        private string $projectPath
    ) {}

    /**
     * Install selected developer tools.
     *
     * @param  array<DeveloperTool>  $tools
     */
    public function install(array $tools): void
    {
        if ($tools === []) {
            return;
        }

        $installables = collect($tools)
            ->map(static fn (DeveloperTool $tool): ?Installable => $tool->installable());

        // Phase 1: Collect all package names
        $dependencies = $installables
            ->flatMap(static fn (Installable $installable): array => $installable->dependencies())
            ->unique()
            ->values()
            ->all();

        $devDependencies = $installables
            ->flatMap(static fn (Installable $installable): array => $installable->devDependencies())
            ->unique()
            ->values()
            ->all();

        // Phase 2: Install all packages at once
        if ( ! empty($dependencies)) {
            $this->composer->installDependencies($dependencies);
        }

        if ( ! empty($devDependencies)) {
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
            $destinationPath = $this->projectPath.DIRECTORY_SEPARATOR.$destination;

            FileEditor::copyFile($source, $destinationPath);
        }
    }
}
