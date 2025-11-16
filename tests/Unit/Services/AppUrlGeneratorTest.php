<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\Services\AppUrlGenerator;

it('generates url with lowercase app name when hostname is resolvable', function (): void {
    $appName = 'MyAwesomeApp';
    $generator = new AppUrlGenerator($appName);
    $url = $generator->generate();
    $hostname = mb_strtolower($appName);

    // Should convert to lowercase, but might fall back to localhost
    if (gethostbyname($hostname.'.') !== '' && gethostbyname($hostname.'.') !== '0') {
        expect($url)->toContain($hostname);
    } else {
        expect($url)->toBe('http://localhost');
    }
});

it('falls back to localhost when hostname cannot be resolved', function (): void {
    $generator = new AppUrlGenerator('MyAwesomeApp');
    $url = $generator->generate();

    // On systems without .test domain resolution (most systems): returns localhost
    // On systems with Valet/Herd: might still resolve .test domains
    expect($url)->toBeString()
        ->and($url)->toStartWith('http://')
        ->and($url)->toMatch('/^http:\/\/(localhost|myawesomeapp\.test)$/');
});

it('handles simple app names', function (): void {
    $generator = new AppUrlGenerator('blog');
    $url = $generator->generate();

    expect($url)->toBeString();
    expect($url)->toStartWith('http://');
});

it('handles app names with dashes', function (): void {
    $generator = new AppUrlGenerator('my-api-app');
    $url = $generator->generate();

    // Will be either http://my-api-app.test or http://localhost
    expect($url)->toBeIn(['http://my-api-app.test', 'http://localhost']);
});

it('handles app names with underscores', function (): void {
    $generator = new AppUrlGenerator('my_api_app');
    $url = $generator->generate();

    // Will be either http://my_api_app.test or http://localhost
    expect($url)->toBeIn(['http://my_api_app.test', 'http://localhost']);
});

it('handles app names with mixed case by converting to lowercase', function (): void {
    $generator = new AppUrlGenerator('MyBlogAPI');
    $url = $generator->generate();

    // Should never contain uppercase
    expect($url)->not->toContain('MyBlogAPI');
    expect($url)->toBeIn(['http://myblogapi.test', 'http://localhost']);
});

it('always returns http protocol', function (): void {
    $generators = [
        new AppUrlGenerator('app1'),
        new AppUrlGenerator('app2'),
        new AppUrlGenerator('APP3'),
    ];

    foreach ($generators as $generator) {
        expect($generator->generate())->toStartWith('http://');
    }
});

it('appends test domain to app name', function (): void {
    $generator = new AppUrlGenerator('myapp');
    $url = $generator->generate();

    // Will be either http://myapp.test or http://localhost
    expect($url)->toBeIn(['http://myapp.test', 'http://localhost']);
});
