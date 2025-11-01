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

it('appends and prepends content', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    $editor->append(PHP_EOL.'Appended line');
    $editor->prepend("Prepended line\n");

    $content = file_get_contents($destinationDirectory.'/sample.txt');

    expect($content)
        ->toStartWith("Prepended line\n")
        ->toContain('Appended line');
});

it('throws when regex pattern is invalid', function () use ($destinationDirectory): void {
    $editor = new TextFileEditor($destinationDirectory.'/sample.txt');

    expect(static fn (): TextFileEditor => $editor->pregReplace(new PregReplacement('/(?:\D+|<\d+>)*[!?]/', 'foobar foobar foobar')))
        ->toThrow(InvalidArgumentException::class, "Invalid regex pattern: '/(?:\D+|<\d+>)*[!?]/':");
});
