<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

use JsonException;

/**
 * A file editor for JSON files.
 */
final class JsonFileEditor extends BaseFileEditor
{
    /**
     * @var array<string, mixed>
     */
    private array $jsonContent;

    /**
     * @throws JsonException
     */
    public function __construct(string $filePath)
    {
        parent::__construct($filePath);

        $this->jsonContent = $this->getJsonContent();
    }

    public function addScript(string $name, string|array $command): self
    {
        if ( ! array_key_exists('scripts', $this->jsonContent)) {
            $this->jsonContent['scripts'] = [];
        }

        $this->jsonContent['scripts'][$name] = $command;

        $this->isChanged = true;

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function save(): bool
    {
        if ($this->isChanged) {
            $content = json_encode($this->jsonContent, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $this->writeFile($content.PHP_EOL);
        }

        return $this->isChanged;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function getJsonContent(): array
    {
        return json_decode($this->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
