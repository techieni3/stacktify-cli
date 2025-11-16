<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Services\Git\NullGitRunner;

it('implements GitClient interface', function (): void {
    $git = new NullGitRunner();

    expect($git)->toBeInstanceOf(GitClient::class);
});

it('init does nothing and does not throw', function (): void {
    $git = new NullGitRunner();

    expect(static fn () => $git->init())->not->toThrow(Exception::class);
});

it('createInitialCommit does nothing and does not throw', function (): void {
    $git = new NullGitRunner();

    expect(static fn () => $git->createInitialCommit())->not->toThrow(Exception::class);
});

it('addAll does nothing and does not throw', function (): void {
    $git = new NullGitRunner();

    expect(static fn () => $git->addAll())->not->toThrow(Exception::class);
});

it('commit does nothing and does not throw', function (): void {
    $git = new NullGitRunner();

    expect(static fn () => $git->commit('test message'))->not->toThrow(Exception::class);
});

it('can be used as safe fallback when git is unavailable', function (): void {
    $git = new NullGitRunner();

    // Should be able to call all methods without errors
    $git->init();
    $git->addAll();
    $git->commit('Initial commit');
    $git->createInitialCommit();

    // No assertions needed - just verifying no exceptions thrown
    expect(true)->toBeTrue();
});
