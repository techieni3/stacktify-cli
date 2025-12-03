<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Techieni3\StacktifyCli\Services\FileEditors\TextFileEditor;
use Techieni3\StacktifyCli\ValueObjects\Replacements\PregReplacement;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

$destinationDirectory = dirname(__DIR__).'/../../Workspace';

beforeEach(function () use ($destinationDirectory): void {
    new Filesystem()->ensureDirectoryExists($destinationDirectory);

    new Filesystem()->copy(
        dirname(__DIR__).'/../../Fixtures/sample.txt',
        $destinationDirectory.'/sample.txt'
    );
});

afterEach(function () use ($destinationDirectory): void {
    if (file_exists($destinationDirectory.'/sample.txt')) {
        unlink($destinationDirectory.'/sample.txt');
    }
});

it('returns false when saving without changes', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    expect($editor->save())->toBeFalse();
});

it('replaces string content', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->replace(new Replacement('World', 'Universe'));

    expect($editor->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)->toContain('Hello Universe');
});

it('processes queued replacements on save', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->queueReplacement(new Replacement('Hello', 'Hi'))
        ->queueReplacement(new Replacement('World', 'Developers'))
        ->queuePregReplacement(new PregReplacement('/Version:\s\d+\.\d+\.\d+/', 'Version: 2.0.0'));

    expect($editor->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toContain('Hi Developers')
        ->toContain('Version: 2.0.0');
});

it('appends content', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->append(PHP_EOL.'Appended line');

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toContain('Appended line');
});

it('prepends content', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->prepend("Prepended line\n");

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toStartWith("Prepended line\n");
});

it('removes lines by text match', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->removeLine('Hello World');

    expect($editor->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->not->toContain('Hello World')
        ->toContain('Version: 1.0.0');
});

it('removes lines using callback', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->removeLine(static fn ($line): bool => str_contains((string) $line, 'Version'));

    expect($editor->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toContain('Hello World')
        ->not->toContain('Version: 1.0.0');
});

it('removes multiple lines using callback', function () use ($destinationDirectory): void {
    // Add more lines to the file
    file_put_contents(
        $destinationDirectory.'/sample.txt',
        "Line 1\nDeprecated Line\nLine 3\nAnother Deprecated\nLine 5\n",
    );

    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->removeLine(static fn ($line): bool => str_contains((string) $line, 'Deprecated'));

    expect($editor->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toContain('Line 1')
        ->toContain('Line 3')
        ->toContain('Line 5')
        ->not->toContain('Deprecated Line')
        ->not->toContain('Another Deprecated');
});

it('does not removes lines with partial text match', function () use ($destinationDirectory): void {
    file_put_contents(
        $destinationDirectory.'/sample.txt',
        "Good line\nBad line to remove\nAnother good line\n",
    );

    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->removeLine('Bad');

    expect($editor->save())->toBeFalse();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toContain('Good line')
        ->toContain('Another good line')
        ->toContain('Bad line to remove');
});

it('does not change file when no lines match', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->removeLine('Nonexistent text');

    expect($editor->save())->toBeFalse();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)->toContain('Hello World')->toContain('Version: 1.0.0');
});

it('removes lines using complex callback logic', function () use ($destinationDirectory): void {
    file_put_contents(
        $destinationDirectory.'/sample.txt',
        "// Comment line\ncode line\n// Another comment\nmore code\n",
    );

    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    // Remove all comment lines
    $editor->removeLine(static fn ($line): bool => str_starts_with(mb_trim((string) $line), '//'));

    expect($editor->save())->toBeTrue();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toContain('code line')
        ->toContain('more code')
        ->not->toContain('// Comment line')
        ->not->toContain('// Another comment');
});

it('can chain removeLine with other operations', function () use ($destinationDirectory): void {
    file_put_contents(
        $destinationDirectory.'/sample.txt',
        "Keep this\nRemove this\nAlso keep\n",
    );

    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor
        ->removeLine('Remove this')
        ->replace(new Replacement('Keep', 'Kept'))
        ->save();

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toContain('Kept this')
        ->toContain('Also keep')
        ->not->toContain('Remove this');
});
