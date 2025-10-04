<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use RuntimeException;
use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

final class FileEditor
{
    private string $filePath;

    private bool $isChanged = false;

    private string $content;

    /**
     * @var Collection<Replacement>
     */
    private Collection $replacements;

    /**
     * @var Collection<PregReplacement>
     */
    private Collection $pregReplacements;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->content = $this->readFile($filePath);

        $this->replacements = collect();
        $this->pregReplacements = collect();
    }

    /**
     * Create a new file editor instance
     */
    public static function open(string $filePath): self
    {
        return new self($filePath);
    }

    /**
     * Copy file to destination
     */
    public static function copyFile(string $sourceFile, string $destination): void
    {
        new Filesystem()->copy($sourceFile, $destination);
    }

    /**
     * Copy directory recursively
     */
    public static function copyDirectory(string $directory, string $destination): void
    {
        new Filesystem()->copyDirectory($directory, $destination);
    }

    /**
     * Static helper for simple replacements
     */
    public static function replaceInFile(string $filePath, Replacement $replacement): void
    {
        $editor = self::open($filePath);
        $editor->replace($replacement);
        $editor->save();
    }

    /**
     * Static helper for regex replacements
     */
    public static function pregReplaceInFile(string $filePath, PregReplacement $replacement): void
    {
        $editor = self::open($filePath);
        $editor->pregReplace($replacement);
        $editor->save();
    }

    /**
     * Append content to the file
     */
    public function append(string $data): void
    {
        new Filesystem()->append($this->filePath, $data);
    }

    /**
     * Prepend content to the file
     */
    public function prepend(string $data): void
    {
        new Filesystem()->prepend($this->filePath, $data);
    }

    /**
     * Perform string replacement
     */
    public function replace(Replacement $replacement): self
    {
        $this->replacements->push($replacement);

        return $this;
    }

    /**
     * Perform regex replacement
     */
    public function pregReplace(PregReplacement $replacement): self
    {
        $this->pregReplacements->push($replacement);

        return $this;
    }

    public function replaceWithFile(string $file): void
    {
        $newContent = $this->readFile($file);

        $this->writeFile($this->filePath, $newContent);
    }

    /**
     * Save changes back to the file
     */
    public function save(): bool
    {
        $this->replacements->each($this->processReplacement(...));

        $this->pregReplacements->each($this->processPregReplacement(...));

        file_put_contents($this->filePath, $this->content);

        return $this->isChanged;
    }

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

    private function writeFile(string $file, string $content): void
    {
        if (file_put_contents($file, $content) === false) {
            throw new RuntimeException("Failed to write file: {$file}");
        }
    }
}
