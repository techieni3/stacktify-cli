<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Contracts;

/**
 * Defines the contract for a Git client.
 */
interface GitClient
{
    /**
     * Initialize a new Git repository.
     */
    public function init(): void;

    /**
     * Perform the initial commit.
     */
    public function initializeRepository(): void;

    /**
     * Stage all changes.
     */
    public function addAll(): void;

    /**
     * Commit the staged changes.
     */
    public function commit(string $message): void;
}
