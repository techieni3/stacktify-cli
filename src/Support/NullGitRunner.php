<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Support;

use Techieni3\StacktifyCli\Contracts\GitClient;

/**
 * A Git client that does nothing.
 */
final class NullGitRunner implements GitClient
{
    /**
     * Initialize a new Git repository.
     */
    public function init(): void {}

    /**
     * Perform the initial commit.
     */
    public function createInitialCommit(): void {}

    /**
     * Stage all changes.
     */
    public function addAll(): void {}

    /**
     * Commit the staged changes.
     */
    public function commit(string $message): void {}
}
