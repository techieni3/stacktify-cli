<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

use RuntimeException;

use function in_array;
use function sprintf;

/**
 * Validates application requirements and preconditions.
 */
final readonly class ApplicationValidator
{
    /**
     * Ensure all required PHP extensions are available.
     *
     * @throws RuntimeException
     */
    public function ensureRequiredExtensionsAreAvailable(): void
    {
        $availableExtensions = get_loaded_extensions();

        $requiredExtensions = [
            'ctype',
            'filter',
            'hash',
            'mbstring',
            'openssl',
            'session',
            'tokenizer',
        ];

        $missingExtensions = array_filter(
            $requiredExtensions,
            static fn (string $extension): bool => ! in_array($extension, $availableExtensions)
        );

        if ($missingExtensions === []) {
            return;
        }

        throw new RuntimeException(sprintf('The following PHP extensions are required but are not installed: %s', implode(', ', $missingExtensions)));
    }

    /**
     * Ensure that the application directory does not already exist.
     *
     * @throws RuntimeException
     */
    public function ensureApplicationDoesNotExist(string $directory): void
    {
        if ($directory !== getcwd() && (is_dir($directory) || is_file($directory))) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Ensure that force installation with current directory is not allowed.
     *
     * @throws RuntimeException
     */
    public function ensureForceNotUsedWithCurrentDirectory(string $directory, bool $force): void
    {
        if ($directory === '.' && $force) {
            throw new RuntimeException('Cannot use --force option when using current directory for installation!');
        }
    }
}
