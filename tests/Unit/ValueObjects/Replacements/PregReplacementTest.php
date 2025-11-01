<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;

it('creates a valid preg replacement with valid regex pattern', function (): void {
    $replacement = new PregReplacement('/foo/', 'bar');

    expect($replacement->regex)->toBe('/foo/')
        ->and($replacement->replace)->toBe('bar');
});

it('creates a valid preg replacement with complex regex pattern', function (): void {
    $pattern = '/^[a-z]+$/i';
    $replacement = new PregReplacement($pattern, 'matched');

    expect($replacement->regex)->toBe($pattern)
        ->and($replacement->replace)->toBe('matched');
});

it('creates a valid preg replacement with regex pattern containing special characters', function (): void {
    $pattern = '/\d{4}-\d{2}-\d{2}/';
    $replacement = new PregReplacement($pattern, 'date');

    expect($replacement->regex)->toBe($pattern)
        ->and($replacement->replace)->toBe('date');
});

it('throws exception when regex pattern is empty', function (): void {
    expect(static fn (): PregReplacement => new PregReplacement('', 'bar'))
        ->toThrow(InvalidArgumentException::class, 'Regex pattern cannot be empty');
});

it('throws exception when regex pattern is invalid', function (): void {
    expect(static fn (): PregReplacement => new PregReplacement('[invalid', 'bar'))
        ->toThrow(InvalidArgumentException::class, 'Invalid regex pattern');
});

it('throws exception when regex pattern has unmatched brackets', function (): void {
    expect(static fn (): PregReplacement => new PregReplacement('/(?:\D+|<\d+>)*[!?/', 'foobar foobar foobar'))
        ->toThrow(InvalidArgumentException::class, 'Invalid regex pattern');
});

it('throws exception when regex pattern lacks delimiters', function (): void {
    // preg_match requires delimiters, so a pattern without them will fail validation
    $pattern = 'a+';
    expect(static fn (): PregReplacement => new PregReplacement($pattern, 'bar'))
        ->toThrow(InvalidArgumentException::class, 'Invalid regex pattern');
});

it('accepts valid regex with word boundaries', function (): void {
    $replacement = new PregReplacement('/\bword\b/', 'replacement');

    expect($replacement->regex)->toBe('/\bword\b/')
        ->and($replacement->replace)->toBe('replacement');
});

it('accepts valid regex with capture groups', function (): void {
    $replacement = new PregReplacement('/(\w+)\s+(\w+)/', '$2 $1');

    expect($replacement->regex)->toBe('/(\w+)\s+(\w+)/')
        ->and($replacement->replace)->toBe('$2 $1');
});

it('accepts valid regex with non-capturing groups', function (): void {
    $replacement = new PregReplacement('/(?:foo|bar)/', 'matched');

    expect($replacement->regex)->toBe('/(?:foo|bar)/')
        ->and($replacement->replace)->toBe('matched');
});

it('allows empty replace string', function (): void {
    $replacement = new PregReplacement('/foo/', '');

    expect($replacement->regex)->toBe('/foo/')
        ->and($replacement->replace)->toBe('');
});
