<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Traits\Prompts;

use Symfony\Component\Process\ExecutableFinder;

use function Laravel\Prompts\select;

trait PromptsForPackageManager
{
    /**
     * Detect available Node package managers and either return the single detected
     * one or prompt the user to choose among multiple options.
     */
    private function detectOrAskPackageManager(): string
    {
        $finder = new ExecutableFinder();
        $available = [];
        foreach (['pnpm', 'bun', 'npm'] as $candidate) {
            if ($finder->find($candidate) !== null) {
                $available[] = $candidate;
            }
        }

        if ($available === [] || $available === ['npm'] || count($available) === 1) {
            return $available[0] ?? 'npm';
        }

        $default = in_array('npm', $available, true) ? 'npm' : $available[0];

        return (string) select(
            label: 'Which package manager would you like to use?',
            options: $available,
            default: $default,
        );
    }
}
