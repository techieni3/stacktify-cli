<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

use Techieni3\StacktifyCli\Services\PhpParser\Visitors\ConfigFileVisitor;

/**
 * Editor for Laravel config files using PHP AST manipulation.
 */
final class ConfigFileEditor extends BasePhpEditor
{
    /**
     * Keys to set with their values.
     *
     * @var array<string, mixed>
     */
    private array $keysToSet = [];

    /**
     * Keys to append values to.
     *
     * @var array<string, array>
     */
    private array $keysToAppend = [];

    /**
     * Keys to merge arrays into.
     *
     * @var array<string, array>
     */
    private array $keysToMerge = [];

    /**
     * Keys to remove.
     *
     * @var array<string>
     */
    private array $keysToRemove = [];

    /**
     * Set a config value (supports dot notation).
     *
     * @param  string  $key  Key to set (e.g., 'app.timezone' or 'timezone')
     * @param  mixed  $value  Value to set (supports primitives, arrays, closures, or PhpParser nodes)
     */
    public function set(string $key, mixed $value): self
    {
        $this->keysToSet[$key] = $value;
        $this->isChanged = true;

        return $this;
    }

    /**
     * Append a value to an array config value.
     *
     * @param  string  $key  Key to append to (e.g., 'providers')
     * @param  mixed  $value  Value to append (supports primitives, arrays, closures, or PhpParser nodes)
     */
    public function append(string $key, mixed $value): self
    {
        if ( ! isset($this->keysToAppend[$key])) {
            $this->keysToAppend[$key] = [];
        }

        $this->keysToAppend[$key][] = $value;
        $this->isChanged = true;

        return $this;
    }

    /**
     * Merge values into an array config value.
     *
     * @param  string  $key  Key to merge into (e.g., 'aliases')
     * @param  array  $values  Values to merge
     */
    public function merge(string $key, array $values): self
    {
        $this->keysToMerge[$key] = $values;
        $this->isChanged = true;

        return $this;
    }

    /**
     * Remove a config key.
     *
     * @param  string  $key  Key to remove
     */
    public function remove(string $key): self
    {
        $this->keysToRemove[] = $key;
        $this->isChanged = true;

        return $this;
    }

    /**
     * Save changes to the config file.
     */
    public function save(): bool
    {
        if ( ! $this->isChanged) {
            return false;
        }

        // Create a visitor with current modifications
        $visitor = new ConfigFileVisitor(
            $this->keysToSet,
            $this->keysToAppend,
            $this->keysToMerge,
            $this->keysToRemove,
        );

        // Traverse and modify the AST
        $modifiedAst = $this->traverse($visitor);

        // Pretty print and save
        $newCode = $this->prettyPrint($modifiedAst);
        $this->writeFile($newCode);

        // Reset state
        $this->keysToSet = [];
        $this->keysToAppend = [];
        $this->keysToMerge = [];
        $this->keysToRemove = [];
        $this->isChanged = false;

        return true;
    }
}
