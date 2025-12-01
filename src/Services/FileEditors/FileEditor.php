<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

use Illuminate\Filesystem\Filesystem;
use JsonException;
use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

/**
 * A utility for editing files.
 */
final class FileEditor
{
    /**
     * Open a text file for editing.
     */
    public static function text(string $filePath): TextFileEditor
    {
        return new TextFileEditor($filePath);
    }

    /**
     * Open an environment file for editing.
     */
    public static function env(string $filePath = '.env'): EnvFileEditor
    {
        return new EnvFileEditor($filePath);
    }

    /**
     * Open a JSON file for editing.
     *
     * @throws JsonException
     */
    public static function json(string $filePath): JsonFileEditor
    {
        return new JsonFileEditor($filePath);
    }

    /**
     * Copy a file to a new location.
     */
    public static function copyFile(string $sourceFile, string $destination): void
    {
        new Filesystem()->copy($sourceFile, $destination);
    }

    /**
     * Recursively copy a directory.
     */
    public static function copyDirectory(string $directory, string $destination): void
    {
        new Filesystem()->copyDirectory($directory, $destination);
    }

    /**
     * Perform a simple string replacement in a file.
     */
    public static function replaceInFile(string $filePath, Replacement $replacement): void
    {
        $editor = self::text($filePath);
        $editor->replace($replacement);
        $editor->save();
    }

    /**
     * Perform a regex replacement in a file.
     */
    public static function pregReplaceInFile(string $filePath, PregReplacement $replacement): void
    {
        $editor = self::text($filePath);
        $editor->pregReplace($replacement);
        $editor->save();
    }
}
