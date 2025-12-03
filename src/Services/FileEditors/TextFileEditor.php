<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

/**
 * A file editor for text files.
 */
final class TextFileEditor extends BaseFileEditor
{
    /**
     * The collection of string replacements.
     *
     * @var Collection<int, Replacement>
     */
    private Collection $replacements;

    /**
     * The collection of regex replacements.
     *
     * @var Collection<int, PregReplacement>
     */
    private Collection $pregReplacements;

    public function __construct(string $filePath)
    {
        parent::__construct($filePath);

        $this->replacements = collect();
        $this->pregReplacements = collect();
    }

    /**
     * Append content to the file.
     */
    public function append(string $data): void
    {
        if ($data === '') {
            return;
        }

        new Filesystem()->append($this->filePath, $data);
    }

    /**
     * Prepend content to the file.
     */
    public function prepend(string $data): void
    {
        if ($data === '') {
            return;
        }

        new Filesystem()->prepend($this->filePath, $data);
    }

    /**
     * Replace all occurrences of a string.
     */
    public function replace(Replacement $replacement): self
    {
        $newContent = str_replace($replacement->search, $replacement->replace, $this->content);

        if ($newContent !== $this->content) {
            $this->isChanged = true;
            $this->content = $newContent;
        }

        return $this;
    }

    /**
     * Replace using a regex pattern.
     */
    public function pregReplace(PregReplacement $replacement): self
    {
        $newContent = @preg_replace($replacement->regex, $replacement->replace, $this->content);

        if ($newContent === null) {
            throw new InvalidArgumentException(
                "Invalid regex pattern: '{$replacement->regex}': ".preg_last_error_msg()
            );
        }

        if ($newContent !== $this->content) {
            $this->isChanged = true;
            $this->content = $newContent;
        }

        return $this;
    }

    /**
     * Remove lines from the file based on a callback or text match.
     */
    public function removeLine(callable|string $text): self
    {
        $lines = explode(PHP_EOL, $this->content);
        $filteredLines = [];

        foreach ($lines as $line) {
            $shouldRemove = is_callable($text)
                ? $text($line)
                : $text === $line;

            if ( ! $shouldRemove) {
                $filteredLines[] = $line;
            }
        }

        $newContent = implode("\n", $filteredLines);

        if ($newContent !== $this->content) {
            $this->isChanged = true;
            $this->content = $newContent;
        }

        return $this;
    }

    /**
     * Queue a Replacement for batch processing.
     */
    public function queueReplacement(Replacement $replacement): self
    {
        $this->replacements->push($replacement);

        return $this;
    }

    /**
     * Queue a PregReplacement for batch processing.
     */
    public function queuePregReplacement(PregReplacement $replacement): self
    {
        $this->pregReplacements->push($replacement);

        return $this;
    }

    /**
     * Save the changes to the file.
     */
    public function save(): bool
    {
        $this->processQueuedReplacements();

        if ($this->isChanged) {
            $this->writeFile($this->content);
        }

        $this->clearQueue();

        return $this->isChanged;
    }

    /**
     * Clear all queued replacements without applying them.
     */
    public function clearQueue(): self
    {
        $this->replacements = collect();
        $this->pregReplacements = collect();

        return $this;
    }

    /**
     * Process all queued replacements.
     */
    private function processQueuedReplacements(): void
    {
        $this->replacements->each(function (Replacement $replacement): void {
            $newContent = str_replace(
                $replacement->search,
                $replacement->replace,
                $this->content
            );

            if ($newContent !== $this->content) {
                $this->isChanged = true;
                $this->content = $newContent;
            }
        });

        $this->pregReplacements->each(function (PregReplacement $replacement): void {
            $newContent = @preg_replace(
                $replacement->regex,
                $replacement->replace,
                $this->content
            );

            if ($newContent === null) {
                throw new InvalidArgumentException(
                    "Invalid regex pattern: '{$replacement->regex}': ".preg_last_error_msg()
                );
            }

            if ($newContent !== $this->content) {
                $this->isChanged = true;
                $this->content = $newContent;
            }
        });
    }
}
