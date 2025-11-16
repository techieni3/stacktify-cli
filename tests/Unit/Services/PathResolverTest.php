<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\Services\PathResolver;

it('resolves installation directory for named project', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app';

    expect($resolver->getInstallationDirectory())->toBe($expected);
});

it('resolves installation directory for current directory', function (): void {
    $resolver = new PathResolver('.');

    expect($resolver->getInstallationDirectory())->toBe('.');
});

it('resolves env file path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'.env';

    expect($resolver->getEnvPath())->toBe($expected);
});

it('resolves env example file path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'.env.example';

    expect($resolver->getEnvExamplePath())->toBe($expected);
});

it('resolves database directory path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'database';

    expect($resolver->getDatabasePath())->toBe($expected);
});

it('resolves sqlite database file path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';

    expect($resolver->getSqliteDatabasePath())->toBe($expected);
});

it('resolves config file path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'app.php';

    expect($resolver->getConfigPath('app.php'))->toBe($expected);
});

it('resolves app directory path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'app';

    expect($resolver->getAppPath())->toBe($expected);
});

it('resolves app subdirectory path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.'User.php';

    expect($resolver->getAppPath('Models/User.php'))->toBe($expected);
});

it('normalizes path separators in app path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR.'Controller.php';

    // Test with forward slashes
    expect($resolver->getAppPath('Http/Controllers/Controller.php'))->toBe($expected)
        ->and($resolver->getAppPath('Http\\Controllers\\Controller.php'))->toBe($expected);
});

it('resolves generic path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';

    expect($resolver->getPath('routes/web.php'))->toBe($expected);
});

it('normalizes path separators in generic path', function (): void {
    $resolver = new PathResolver('my-app');
    $expected = getcwd().DIRECTORY_SEPARATOR.'my-app'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'welcome.blade.php';

    // Test with forward slashes
    expect($resolver->getPath('resources/views/welcome.blade.php'))->toBe($expected)
        ->and($resolver->getPath('resources\\views\\welcome.blade.php'))->toBe($expected);
});

it('works with current directory for all paths', function (): void {
    $resolver = new PathResolver('.');

    expect($resolver->getEnvPath())->toBe('.'.DIRECTORY_SEPARATOR.'.env')
        ->and($resolver->getDatabasePath())->toBe('.'.DIRECTORY_SEPARATOR.'database')
        ->and($resolver->getConfigPath('app.php'))->toBe('.'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'app.php');
});
