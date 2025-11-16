<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\Services\ApplicationValidator;

it('ensures application does not exist when directory is missing', function (): void {
    $validator = new ApplicationValidator();
    $nonExistentDir = sys_get_temp_dir().'/stacktify-test-'.uniqid('', true);

    // Should not throw exception for non-existent directory
    expect(static fn () => $validator->ensureApplicationDoesNotExist($nonExistentDir))
        ->not->toThrow(RuntimeException::class);
});

it('throws exception when application directory already exists', function (): void {
    $validator = new ApplicationValidator();
    $tempDir = sys_get_temp_dir().'/stacktify-test-'.uniqid('', true);

    mkdir($tempDir);

    try {
        expect(static fn () => $validator->ensureApplicationDoesNotExist($tempDir))
            ->toThrow(RuntimeException::class, 'Application already exists!');
    } finally {
        rmdir($tempDir);
    }
});

it('throws exception when application file already exists at path', function (): void {
    $validator = new ApplicationValidator();
    $tempFile = sys_get_temp_dir().'/stacktify-test-'.uniqid('', true);

    touch($tempFile);

    try {
        expect(static fn () => $validator->ensureApplicationDoesNotExist($tempFile))
            ->toThrow(RuntimeException::class, 'Application already exists!');
    } finally {
        unlink($tempFile);
    }
});

it('allows current working directory as installation path', function (): void {
    $validator = new ApplicationValidator();

    // Current working directory should not throw exception
    expect(static fn () => $validator->ensureApplicationDoesNotExist(getcwd()))
        ->not->toThrow(RuntimeException::class);
});

it('throws exception when force is used with current directory', function (): void {
    $validator = new ApplicationValidator();

    expect(static fn () => $validator->ensureForceNotUsedWithCurrentDirectory('.', true))
        ->toThrow(RuntimeException::class, 'Cannot use --force option when using current directory for installation!');
});

it('allows force with non-current directory', function (): void {
    $validator = new ApplicationValidator();

    expect(static fn () => $validator->ensureForceNotUsedWithCurrentDirectory('/some/other/path', true))
        ->not->toThrow(RuntimeException::class);
});

it('allows current directory without force', function (): void {
    $validator = new ApplicationValidator();

    expect(static fn () => $validator->ensureForceNotUsedWithCurrentDirectory('.', false))
        ->not->toThrow(RuntimeException::class);
});

it('ensures required extensions are available', function (): void {
    $validator = new ApplicationValidator();

    // This test will pass if all required extensions are installed
    // In a real environment, all these extensions should be present
    $requiredExtensions = ['ctype', 'filter', 'hash', 'mbstring', 'openssl', 'session', 'tokenizer'];
    $availableExtensions = get_loaded_extensions();
    $allPresent = array_all($requiredExtensions, static fn ($ext): bool => in_array($ext, $availableExtensions));

    if ($allPresent) {
        // If all extensions are present, should not throw
        expect(static fn () => $validator->ensureRequiredExtensionsAreAvailable())
            ->not->toThrow(RuntimeException::class);
    } else {
        // If any extension is missing, should throw
        expect(static fn () => $validator->ensureRequiredExtensionsAreAvailable())
            ->toThrow(RuntimeException::class);
    }
});
