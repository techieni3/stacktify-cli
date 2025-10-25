<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

use RuntimeException;

/**
 * Base class for file editors.
 */
abstract class BaseFileEditor
{
    /**
     * Indicates if the file has been changed.
     */
    protected bool $isChanged = false;

    /**
     * The content of the file.
     */
    protected string $content;

    public function __construct(protected readonly string $filePath)
    {
        $this->content = $this->readFile();
    }

    /**
     * Save changes to the file.
     */
    abstract public function save(): bool;

    /**
     * Get the current content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get the file path.
     */
    public function getPath(): string
    {
        return $this->filePath;
    }

    /**
     * Write content to a file.
     */
    protected function writeFile(string $content): void
    {
        if (file_put_contents($this->filePath, $content) === false) {
            throw new RuntimeException("Failed to write file: {$this->filePath}");
        }
    }

    /**
     * Read the content of a file.
     */
    private function readFile(): string
    {
        if ( ! file_exists($this->filePath)) {
            throw new RuntimeException("File not found: {$this->filePath}");
        }

        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new RuntimeException("Failed to read file: {$this->filePath}");
        }

        return $content;
    }
}
