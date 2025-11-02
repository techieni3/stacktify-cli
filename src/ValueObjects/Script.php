<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\ValueObjects;

use InvalidArgumentException;

/**
 * A generic script value object for package manager scripts.
 */
final readonly class Script
{
    /**
     * Create a new Script instance.
     *
     * @param  string|array<string>  $command
     *
     * @throws InvalidArgumentException
     */
    public function __construct(private string $name, private string|array $command)
    {
        $this->validate();
    }

    /**
     * Get the script name.
     */
    public function getName(): string
    {
        return mb_trim($this->name);
    }

    /**
     * Get the script command(s).
     *
     * @return string|array<string>
     */
    public function getCommand(): string|array
    {
        if (is_string($this->command)) {
            return mb_trim($this->command);
        }

        return array_map(trim(...), $this->command);
    }

    /**
     * Validate the script data.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        if (mb_trim($this->name) === '') {
            throw new InvalidArgumentException('Script name cannot be empty.');
        }

        if (is_string($this->command) && mb_trim($this->command) === '') {
            throw new InvalidArgumentException('Script command cannot be empty.');
        }

        if (is_array($this->command)) {
            if ($this->command === []) {
                throw new InvalidArgumentException('Script command array cannot be empty.');
            }

            // Check if array is sequential (not associative)
            if ( ! array_is_list($this->command)) {
                throw new InvalidArgumentException('Script command array must be sequential (not associative).');
            }

            foreach ($this->command as $command) {
                /** @phpstan-ignore-next-line */
                if ( ! is_string($command)) {
                    throw new InvalidArgumentException('Script command array values must be strings.');
                }

                if (mb_trim($command) === '') {
                    throw new InvalidArgumentException('Script command array values cannot be empty.');
                }
            }
        }
    }
}
