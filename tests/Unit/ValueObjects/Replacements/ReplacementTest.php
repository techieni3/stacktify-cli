<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

it('creates a valid replacement with string search and replace', function (): void {
    $replacement = new Replacement('foo', 'bar');

    expect($replacement->search)->toBe('foo')
        ->and($replacement->replace)->toBe('bar');
});

it('creates a valid replacement with array search and replace', function (): void {
    $search = ['foo', 'baz'];
    $replace = ['bar', 'qux'];
    $replacement = new Replacement($search, $replace);

    expect($replacement->search)->toBe($search)
        ->and($replacement->replace)->toBe($replace);
});

it('throws exception when search is string but replace is array', function (): void {
    expect(static fn (): Replacement => new Replacement('foo', ['bar']))
        ->toThrow(InvalidArgumentException::class, 'If search is an string, replace must also be a string.');
});

it('throws exception when search is array but replace is string', function (): void {
    $search = ['foo'];
    $replace = 'bar';
    $replacement = new Replacement($search, $replace);

    expect($replacement->search)->toBe($search)
        ->and($replacement->replace)->toBe($replace);
});

it('throws exception when search and replace arrays have different lengths', function (): void {
    expect(static fn (): Replacement => new Replacement(['foo', 'baz'], ['bar']))
        ->toThrow(InvalidArgumentException::class, 'Search and replace arrays must have the same number of elements.');
});

it('throws exception when search array is empty', function (): void {
    expect(static fn (): Replacement => new Replacement([], []))
        ->toThrow(InvalidArgumentException::class, 'Search array cannot be empty.');
});

it('throws exception when search string is empty', function (): void {
    expect(static fn (): Replacement => new Replacement('', 'bar'))
        ->toThrow(InvalidArgumentException::class, 'Search value cannot be an empty string.');
});

it('throws exception when search array contains non-string values', function (): void {
    expect(static fn (): Replacement => new Replacement(['foo', 123], ['bar', 'qux']))
        ->toThrow(InvalidArgumentException::class, 'Search array must contain only strings.');
});

it('throws exception when search array contains empty strings', function (): void {
    expect(static fn (): Replacement => new Replacement(['foo', ''], ['bar', 'qux']))
        ->toThrow(InvalidArgumentException::class, 'Search values in array cannot be empty strings.');
});

it('handles single element arrays', function (): void {
    $replacement = new Replacement(['foo'], ['bar']);

    expect($replacement->search)->toBe(['foo'])
        ->and($replacement->replace)->toBe(['bar']);
});

it('handles multiple element arrays', function (): void {
    $search = ['foo', 'baz', 'qux'];
    $replace = ['bar', 'quux', 'corge'];
    $replacement = new Replacement($search, $replace);

    expect($replacement->search)->toBe($search)
        ->and($replacement->replace)->toBe($replace);
});
