<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

use JsonException;
use Techieni3\StacktifyCli\ValueObjects\Script;

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

    /**
     * Check if a script exists.
     */
    public function hasScript(string $name): bool
    {
        return isset($this->jsonContent['scripts'][$name]);
    }

    /**
     * Add a script to the JSON content.
     */
    public function addScript(Script $script): self
    {
        if ( ! $this->hasScriptsSection()) {
            $this->jsonContent['scripts'] = [];
        }

        $this->jsonContent['scripts'][$script->getName()] = $script->getCommand();

        $this->isChanged = true;

        return $this;
    }

    /**
     * Append a command to an existing script.
     *
     * @param  string|array<string>  $command
     */
    public function appendToScript(string $name, string|array $command): self
    {
        if ( ! $this->hasScriptsSection()) {
            $this->jsonContent['scripts'] = [];
        }

        if ( ! $this->hasScript($name)) {
            // Convert command to array if it's a string
            $commandsToAdd = is_array($command) ? $command : [$command];
            // Script doesn't exist, create it as an array
            $this->jsonContent['scripts'][$name] = $commandsToAdd;
        } else {
            $existingValue = $this->jsonContent['scripts'][$name];

            // Convert an existing value to array if it's a string
            if (is_string($existingValue)) {
                $existingValue = [$existingValue];
            }

            // Append new commands to an existing array
            is_array($command) ? $existingValue = [...$existingValue, ...$command] : $existingValue[] = $command;

            $this->jsonContent['scripts'][$name] = $existingValue;
        }

        $this->isChanged = true;

        return $this;
    }

    /**
     * Set a value in the JSON content using dot notation.
     */
    public function set(string $key, mixed $value): self
    {
        // Normalize value: always store as an array, unique if an array given
        $newValue = is_array($value) ? array_unique(array_unique($value)) : $value;

        $keys = explode('.', $key);
        $current = &$this->jsonContent;
        $lastKey = array_key_last($keys);

        foreach ($keys as $i => $nestedKey) {
            if ($i === $lastKey) {
                $current[$nestedKey] = $newValue;
                break;
            }

            // Ensure the path exists and is an array
            if ( ! isset($current[$nestedKey]) || ! is_array($current[$nestedKey])) {
                $current[$nestedKey] = [];
            }

            $current = &$current[$nestedKey];
        }

        $this->isChanged = true;

        return $this;
    }

    /**
     * Get a value from the JSON content using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $current = $this->jsonContent;

        foreach ($keys as $nestedKey) {
            if (
                ! is_array($current) ||
                ! array_key_exists($nestedKey, $current)
            ) {
                return $default;
            }

            $current = $current[$nestedKey];
        }

        return $current;
    }

    /**
     * Check if a key exists in the JSON content using dot notation.
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $current = $this->jsonContent;

        foreach ($keys as $nestedKey) {
            if (
                ! is_array($current) ||
                ! array_key_exists($nestedKey, $current)
            ) {
                return false;
            }

            $current = $current[$nestedKey];
        }

        return true;
    }

    /**
     * Append a value to an array in the JSON content using dot notation.
     */
    public function append(string $key, mixed $value): self
    {
        $current = $this->get($key, []);

        if ( ! is_array($current)) {
            $current = [$current];
        }

        $current[] = $value;

        return $this->set($key, $current);
    }

    /**
     * Merge values into an array in the JSON content using dot notation.
     */
    public function merge(string $key, array $values): self
    {
        $current = $this->get($key, []);

        if ( ! is_array($current)) {
            $current = [];
        }

        $merged = [...$current, ...$values];

        return $this->set($key, $merged);
    }

    /**
     * Remove a value from an array in the JSON content using dot notation.
     */
    public function removeValue(string $key, mixed $value): self
    {
        $current = $this->get($key);

        if (is_array($current)) {
            $filtered = array_values(
                array_filter($current, static fn ($item): bool => $item !== $value),
            );
            $this->set($key, $filtered);
        }

        return $this;
    }

    /**
     * Delete a key from the JSON content using dot notation.
     */
    public function delete(string $key): self
    {
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$this->jsonContent;

        foreach ($keys as $nestedKey) {
            if (
                ! is_array($current) ||
                ! array_key_exists($nestedKey, $current)
            ) {
                return $this;
            }

            $current = &$current[$nestedKey];
        }

        if (is_array($current) && array_key_exists($lastKey, $current)) {
            unset($current[$lastKey]);
            $this->isChanged = true;
        }

        return $this;
    }

    /**
     * Remove a script from the JSON content.
     */
    public function removeScript(string $name): self
    {
        if ($this->hasScript($name)) {
            unset($this->jsonContent['scripts'][$name]);
            $this->isChanged = true;
        }

        return $this;
    }

    /**
     * Update an existing script or add if it doesn't exist.
     */
    public function updateScript(Script $script): self
    {
        return $this->addScript($script);
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

    /**
     * Check if the JSON content has a scripts section.
     */
    private function hasScriptsSection(): bool
    {
        return array_key_exists('scripts', $this->jsonContent);
    }
}
