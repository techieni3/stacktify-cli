<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Helper for writing consistent console notifications.
 */
final readonly class ConsoleNotifier
{
    public function __construct(private OutputInterface $output) {}

    /**
     * Write a formatted success message to the output.
     */
    public function success(string $message): void
    {
        $this->output->writeln(sprintf('<info> ✅ </info> %s', $message));
    }
}
