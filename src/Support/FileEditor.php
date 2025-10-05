<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use RuntimeException;
use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

/**
 * A utility for editing files.
 */
final class FileEditor
{
    /**
     * Indicates if the file has been changed.
     */
    private bool $isChanged = false;

    /**
     * The content of the file.
     */
    private string $content;

    /**
     * The collection of string replacements.
     *
     * @var Collection<int, Replacement>
     */
    private readonly Collection $replacements;

    /**
     * The collection of regex replacements.
     *
     * @var Collection<int, PregReplacement>
     */
    private readonly Collection $pregReplacements;

    /**
     * Create a new FileEditor instance.
     */
    public function __construct(
        /**
         * The path to the file.
         */
        private readonly string $filePath
    ) {
        $this->content = $this->readFile($this->filePath);

        $this->replacements = collect();
        $this->pregReplacements = collect();
    }

    /**
     * Create a new file editor instance.
     */
    public static function open(string $filePath): self
    {
        return new self($filePath);
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
        $editor = self::open($filePath);
        $editor->replace($replacement);
        $editor->save();
    }

    /**
     * Perform a regex replacement in a file.
     */
    public static function pregReplaceInFile(string $filePath, PregReplacement $replacement): void
    {
        $editor = self::open($filePath);
        $editor->pregReplace($replacement);
        $editor->save();
    }

    /**
     * Append content to the file.
     */
    public function append(string $data): void
    {
        new Filesystem()->append($this->filePath, $data);
    }

    /**
     * Prepend content to the file.
     */
    public function prepend(string $data): void
    {
        new Filesystem()->prepend($this->filePath, $data);
    }

    /**
     * Add a string replacement to the queue.
     */
    public function replace(Replacement $replacement): self
    {
        $this->replacements->push($replacement);

        return $this;
    }

    /**
     * Add a regex replacement to the queue.
     */
    public function pregReplace(PregReplacement $replacement): self
    {
        $this->pregReplacements->push($replacement);

        return $this;
    }

    /**
     * Replace the file content with the content of another file.
     */
    public function replaceWithFile(string $file): void
    {
        $newContent = $this->readFile($file);

        $this->writeFile($this->filePath, $newContent);
    }

    /**
     * Save the changes to the file.
     */
    public function save(): bool
    {
        $this->replacements->each($this->processReplacement(...));

        $this->pregReplacements->each($this->processPregReplacement(...));

        file_put_contents($this->filePath, $this->content);

        return $this->isChanged;
    }

    /**
     * Read the content of a file.
     */
    private function readFile(string $file): string
    {
        if ( ! file_exists($file)) {
            throw new RuntimeException("File not found: {$file}");
        }

        $content = file_get_contents($file);

        if ($content === false) {
            throw new RuntimeException("Failed to read file: {$file}");
        }

        return $content;
    }

    /**
     * Process a string replacement.
     */
    private function processReplacement(Replacement $replacement): void
    {
        $newContent = str_replace(
            search: $replacement->search,
            replace: $replacement->replace,
            subject: $this->content
        );

        $this->isChanged = $newContent !== $this->content;
        $this->content = $newContent;
    }

    /**
     * Process a regex replacement.
     */
    private function processPregReplacement(PregReplacement $replacement): void
    {
        $newContent = preg_replace(
            pattern: $replacement->regex,
            replacement: $replacement->replace,
            subject: $this->content
        );

        if ($newContent === null) {
            throw new RuntimeException("Regex error in pattern: {$replacement->regex}");
        }

        $this->isChanged = $newContent !== $this->content;
        $this->content = $newContent;
    }

    /**
     * Write content to a file.
     */
    private function writeFile(string $file, string $content): void
    {
        if (file_put_contents($file, $content) === false) {
            throw new RuntimeException("Failed to write file: {$file}");
        }
    }
}
