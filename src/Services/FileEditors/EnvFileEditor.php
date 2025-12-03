<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\FileEditors;

/**
 * A file editor for .env files with auto-quoting support.
 */
final class EnvFileEditor extends BaseFileEditor
{
    /**
     * All file lines with metadata.
     *
     * @var array<int, array{type: string, key?: string, value?: string, content?: string, quoted?: bool, commented?: bool, modified?: bool}>
     */
    private array $lines = [];

    /**
     * Line number for the last entry (used for appending new keys).
     */
    private int $lastLineNumber = 0;

    /**
     * Keys that should always be quoted (set via setQuoted).
     *
     * @var array<string, bool>
     */
    private array $forceQuoted = [];

    public function __construct(string $filePath)
    {
        parent::__construct($filePath);
        $this->parse();
    }

    /**
     * Check if a key exists in the .env file.
     */
    public function has(string $key): bool
    {
        return $this->findLineByKey($key) !== null;
    }

    /**
     * Get a value by key.
     */
    public function get(string $key): ?string
    {
        $line = $this->findLineByKey($key);

        return $line['value'] ?? null;
    }

    /**
     * Get all key-value pairs.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $result = [];

        foreach ($this->lines as $line) {
            if ($line['type'] === 'key') {
                $result[$line['key']] = $line['value'];
            }
        }

        return $result;
    }

    /**
     * Set one or multiple values with automatic quoting.
     *
     * @param  array<string, string>|string  $key
     */
    public function set(array|string $key, string $value = ''): self
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $k => $v) {
            $this->setSingle($k, $v);
        }

        return $this;
    }

    /**
     * Add an empty line.
     */
    public function emptyLine(): self
    {
        $this->lines[] = [
            'type' => 'empty',
        ];

        $this->isChanged = true;

        return $this;
    }

    /**
     * Set with forced quoting (even if not needed).
     */
    public function setQuoted(string $key, string $value): self
    {
        $this->forceQuoted[$key] = true;

        $this->setSingle($key, $value);

        return $this;
    }

    /**
     * Set boolean value (converts to 'true'/'false' strings).
     */
    public function setBoolean(string $key, bool $value): self
    {
        $this->setSingle($key, $value ? 'true' : 'false');

        return $this;
    }

    /**
     * Delete a key from the .env file.
     */
    public function delete(string $key): self
    {
        foreach ($this->lines as $index => $line) {
            if ($line['type'] === 'key' && $line['key'] === $key) {
                unset($this->lines[$index]);
                $this->isChanged = true;
                break;
            }
        }

        return $this;
    }

    /**
     * Comment one or multiple keys.
     *
     * @param  string|array<string>  $keys
     */
    public function comment(string|array $keys): self
    {
        $keysArray = $this->normalizeKeys($keys);

        foreach ($this->lines as &$line) {
            if ($line['type'] === 'key' && in_array($line['key'], $keysArray, true)) {
                $line['commented'] = true;
                $this->isChanged = true;
            }
        }

        return $this;
    }

    /**
     * Uncomment one or multiple keys.
     *
     * @param  string|array<string>  $keys
     */
    public function uncomment(string|array $keys): self
    {
        $keysArray = $this->normalizeKeys($keys);

        foreach ($this->lines as &$line) {
            if ($line['type'] === 'key' && in_array($line['key'], $keysArray, true)) {
                $line['commented'] = false;
                $this->isChanged = true;
            }
        }

        return $this;
    }

    /**
     * Check if a key is commented.
     */
    public function isCommented(string $key): bool
    {
        $line = $this->findLineByKey($key);

        return $line['commented'] ?? false;
    }

    // ==================== PERSISTENCE ====================

    /**
     * Save changes to the file.
     */
    public function save(): bool
    {
        if ($this->isChanged) {
            $content = $this->serialize();
            $this->writeFile($content);
        }

        return $this->isChanged;
    }

    /**
     * Find a line by key.
     *
     * @return array{type: string, key: string, value: string, quoted: bool, commented: bool, modified?: bool}|null
     */
    private function findLineByKey(string $key): ?array
    {
        return array_find($this->lines, static fn ($line): bool => $line['type'] === 'key' && $line['key'] === $key);

    }

    /**
     * Internal method to set a single value.
     */
    private function setSingle(string $key, string $value): void
    {
        // Find the existing key and update it
        foreach ($this->lines as &$line) {
            if ($line['type'] === 'key' && $line['key'] === $key) {
                $line['value'] = $value;
                $line['commented'] = false;
                $line['modified'] = true;
                $this->isChanged = true;

                return;
            }
        }

        unset($line);

        // Key doesn't exist - append at the end
        $this->lastLineNumber++;
        $this->lines[] = [
            'type' => 'key',
            'key' => $key,
            'value' => $value,
            'quoted' => false,
            'commented' => false,
            'modified' => true,
        ];

        $this->isChanged = true;
    }

    /**
     * Determine if a value needs quoting.
     */
    private function shouldQuote(?string $value): bool
    {
        if ($value === '' || $value === null) {
            return false;
        }

        return str_contains($value, ' ')      // Has spaces
            || str_contains($value, '#')      // Has hash (would start comment)
            || str_contains($value, '"')      // Has quotes
            || str_contains($value, "'")      // Has single quotes
            || str_contains($value, "\n")     // Has newlines
            || str_contains($value, '=');     // Has equals
    }

    /**
     * Quote a value properly.
     */
    private function quoteValue(string $value): string
    {
        $escaped = addslashes($value);

        return "\"{$escaped}\"";
    }

    /**
     * Remove quotes from a value if present.
     */
    private function unquoteValue(string $value): string
    {
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            return stripslashes($matches[1]);
        }

        if (preg_match("/^'(.*)'$/", $value, $matches)) {
            return stripslashes($matches[1]);
        }

        return $value;
    }

    /**
     * Check if a value is quoted in the original content.
     */
    private function isValueQuoted(string $value): bool
    {
        return (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"));
    }

    /**
     * Parse the .env file content.
     */
    private function parse(): void
    {
        $rawLines = explode(PHP_EOL, $this->content);

        foreach ($rawLines as $line) {
            $trimmed = mb_trim($line);

            // Empty line
            if ($trimmed === '') {
                $this->lines[] = ['type' => 'empty'];

                continue;
            }

            // Comment line (not a commented key)
            if (str_starts_with($trimmed, '#') && ! str_contains($trimmed, '=')) {
                $this->lines[] = [
                    'type' => 'comment',
                    'content' => $trimmed,
                ];

                continue;
            }

            // Commented key: # KEY=value
            if (str_starts_with($trimmed, '#') && str_contains($trimmed, '=')) {
                $uncommented = mb_ltrim($trimmed, '# ');
                [$key, $value, $wasQuoted] = $this->parseLine($uncommented);

                if ($key !== null) {
                    $this->lines[] = [
                        'type' => 'key',
                        'key' => $key,
                        'value' => $value,
                        'quoted' => $wasQuoted,
                        'commented' => true,
                    ];
                }

                continue;
            }

            // Regular key: KEY=value
            if (str_contains($trimmed, '=')) {
                [$key, $value, $wasQuoted] = $this->parseLine($trimmed);

                if ($key !== null) {
                    $this->lines[] = [
                        'type' => 'key',
                        'key' => $key,
                        'value' => $value,
                        'quoted' => $wasQuoted,
                        'commented' => false,
                    ];
                }
            }
        }

        $this->lastLineNumber = count($this->lines);
    }

    /**
     * Parse a single line into key and value.
     *
     * @return array{0: string|null, 1: string, 2: bool}
     */
    private function parseLine(string $line): array
    {
        // Split on the first '=' only
        $parts = explode('=', $line, 2);

        if (count($parts) !== 2) {
            return [null, '', false];
        }

        $key = mb_trim($parts[0]);
        $rawValue = mb_trim($parts[1]);

        // Check if the value was quoted BEFORE unquoting
        $wasQuoted = $this->isValueQuoted($rawValue);

        // Unquote the value
        $value = $this->unquoteValue($rawValue);

        return [$key, $value, $wasQuoted];
    }

    /**
     * Serialize back to .env format.
     */
    private function serialize(): string
    {
        $output = [];

        foreach ($this->lines as $line) {
            if ($line['type'] === 'empty') {
                $output[] = '';

                continue;
            }

            if ($line['type'] === 'comment') {
                $output[] = $line['content'];

                continue;
            }

            if ($line['type'] === 'key') {
                $key = $line['key'];
                $value = $line['value'];

                // Format the value with proper quoting
                $formattedValue = $this->formatValue($key, $value, $line);

                $outputLine = "{$key}={$formattedValue}";

                // Add a comment prefix if needed
                if ($line['commented']) {
                    $outputLine = "# {$outputLine}";
                }

                $output[] = $outputLine;
            }
        }

        return implode(PHP_EOL, $output);
    }

    /**
     * Format a value for output (apply quoting if needed).
     *
     * @param  array<string, string>|array<string, bool>  $line
     */
    private function formatValue(string $key, string $value, array $line): string
    {
        // If force-quoted (via setQuoted), always quote
        if (isset($this->forceQuoted[$key])) {
            return $this->quoteValue($value);
        }

        // If the value was modified, use auto-quoting
        if ($line['modified'] ?? false) {
            return $this->shouldQuote($value) ? $this->quoteValue($value) : $value;
        }

        // Otherwise, preserve the original quoting style
        if ($line['quoted'] ?? false) {
            return $this->quoteValue($value);
        }

        return $value;
    }

    /**
     * @return array|string[]
     */
    private function normalizeKeys(array|string $keys): array
    {
        return is_array($keys) ? $keys : [$keys];
    }
}
