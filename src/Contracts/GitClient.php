<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Contracts;

interface GitClient
{
    public function init(): void;

    public function initializeRepository(): void;

    public function addAll(): void;

    public function commit(string $message): void;
}
