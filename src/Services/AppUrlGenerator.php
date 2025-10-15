<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services;

final readonly class AppUrlGenerator
{
    public function __construct(private string $appName) {}

    /**
     * Generate the application URL.
     */
    public function generate(): string
    {
        $hostname = mb_strtolower($this->appName).'.test';

        return $this->canResolveHostname($hostname)
            ? 'http://'.$hostname
            : 'http://localhost';
    }

    /**
     * Determine if the given hostname can be resolved.
     */
    private function canResolveHostname(string $hostname): bool
    {
        return gethostbyname($hostname.'.') !== $hostname.'.';
    }
}
