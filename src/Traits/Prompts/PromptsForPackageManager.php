<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Traits\Prompts;

use Techieni3\StacktifyCli\Enums\NodePackageManager;
use Techieni3\StacktifyCli\Services\ExecutableLocator;

use function count;
use function Laravel\Prompts\select;

/**
 * Provide reusable prompts for selecting a Node package manager.
 */
trait PromptsForPackageManager
{
    /**
     * Detect an available package manager or prompt the user to choose one.
     */
    private function detectOrAskPackageManager(): NodePackageManager
    {
        $finder = new ExecutableLocator();
        $availablePackageManagers = [];
        foreach (NodePackageManager::cases() as $packageManager) {
            if ($finder->findExecutable($packageManager->executable()) !== null) {
                $availablePackageManagers[] = $packageManager;
            }
        }

        if ($availablePackageManagers === [] || $availablePackageManagers === [NodePackageManager::Npm] || count($availablePackageManagers) === 1) {
            return $availablePackageManagers[0] ?? NodePackageManager::Npm;
        }

        return NodePackageManager::from(
            (string) select(
                label: 'Which package manager would you like to use?',
                options: array_reduce(
                    $availablePackageManagers,
                    static fn ($carry, $packageManager): array => $carry + [$packageManager->value => $packageManager->label()],
                    []
                ),
                default: NodePackageManager::default(),
            )
        );
    }
}
