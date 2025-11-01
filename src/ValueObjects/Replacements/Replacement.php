<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\ValueObjects\Replacements;

use InvalidArgumentException;

/**
 * A value object representing a string replacement.
 */
final readonly class Replacement
{
    /**
     * Create a new Replacement instance.
     *
     * @param  array<int, string>  $search
     * @param  array<int, string>  $replace
     */
    public function __construct(public string|array $search, public string|array $replace)
    {
        $this->validate();
    }

    /**
     * Validate the search value.
     */
    private function validate(): void
    {
        // search and replace must both be strings or both be arrays
        if (is_string($this->search) && is_array($this->replace)) {
            throw new InvalidArgumentException('If search is an string, replace must also be a string.');
        }

        // If both are arrays, they must have the same number of elements
        if (is_array($this->search) && is_array($this->replace) && count($this->search) !== count($this->replace)) {
            throw new InvalidArgumentException('Search and replace arrays must have the same number of elements.');
        }

        if (is_array($this->search)) {
            if ($this->search === []) {
                throw new InvalidArgumentException('Search array cannot be empty.');
            }

            foreach ($this->search as $item) {
                if ( ! is_string($item)) {
                    throw new InvalidArgumentException('Search array must contain only strings.');
                }

                if ($item === '') {
                    throw new InvalidArgumentException('Search values in array cannot be empty strings.');
                }
            }
        } elseif ($this->search === '') {
            throw new InvalidArgumentException('Search value cannot be an empty string.');
        }

    }
}
