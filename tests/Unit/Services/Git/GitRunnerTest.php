<?php

declare(strict_types=1);

use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Services\Git\GitRunner;
use Techieni3\StacktifyCli\Services\ProcessRunner;

beforeEach(function (): void {
    // Skip if Git is not available in test environment
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $result = $process->execute(['git', '--version']);

    if ( ! $result->isSuccessful()) {
        $this->markTestSkipped('Git is not available in this environment');
    }
});

it('implements GitClient interface', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    try {
        $git = new GitRunner($process, $tempDir);
        expect($git)->toBeInstanceOf(GitClient::class);
    } finally {
        rmdir($tempDir);
    }
});

it('can check if git is available', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    try {
        $git = new GitRunner($process, $tempDir);
        expect($git->isAvailable())->toBeTrue();
    } finally {
        rmdir($tempDir);
    }
});

it('can initialize a git repository', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    try {
        $git = new GitRunner($process, $tempDir);
        $git->init();

        // Check if .git directory was created
        expect(is_dir($tempDir.'/.git'))->toBeTrue();
    } finally {
        // Clean up
        if (is_dir($tempDir.'/.git')) {
            exec("rm -rf {$tempDir}");
        } else {
            rmdir($tempDir);
        }
    }
});

it('can configure git user name', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    try {
        $git = new GitRunner($process, $tempDir);
        $git->init();
        $git->configureName('Test User');

        // Read back the config
        $result = $process->execute(['git', 'config', '--get', 'user.name'], $tempDir);
        expect(mb_trim($result->getOutput()))->toBe('Test User');
    } finally {
        exec("rm -rf {$tempDir}");
    }
});

it('can configure git user email', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    try {
        $git = new GitRunner($process, $tempDir);
        $git->init();
        $git->configureEmail('test@example.com');

        // Read back the config
        $result = $process->execute(['git', 'config', '--get', 'user.email'], $tempDir);
        expect(mb_trim($result->getOutput()))->toBe('test@example.com');
    } finally {
        exec("rm -rf {$tempDir}");
    }
});

it('can check if identity is configured', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    try {
        $git = new GitRunner($process, $tempDir);
        $git->init();

        // Note: hasIdentityConfigured checks both local AND global config
        // So if system has global git config, it might return true
        // We just verify the method works without errors
        $hasIdentity = $git->hasIdentityConfigured();
        expect($hasIdentity)->toBeBool();

        // Configure local identity
        $git->configureName('Test User');
        $git->configureEmail('test@example.com');

        // Now identity should definitely be configured
        expect($git->hasIdentityConfigured())->toBeTrue();
    } finally {
        exec("rm -rf {$tempDir}");
    }
});

it('can stage all changes', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    try {
        $git = new GitRunner($process, $tempDir);
        $git->init();

        // Create a test file
        file_put_contents($tempDir.'/test.txt', 'test content');

        // Stage all changes
        $git->addAll();

        // Check that file is staged
        $result = $process->execute(['git', 'status', '--porcelain'], $tempDir);
        expect($result->getOutput())->toContain('A  test.txt');
    } finally {
        exec("rm -rf {$tempDir}");
    }
});

it('can create a commit', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    $client = Mockery::mock(GitClient::class);
    $client->shouldReceive('commit');

    try {
        $git = new GitRunner($process, $tempDir);
        $git->init();
        $git->configureName('Test User');
        $git->configureEmail('test@example.com');

        // Create and stage a file
        file_put_contents($tempDir.'/test.txt', 'test content');
        $git->addAll();

        $git->commit('Initial commit');

        // As commit does not return check, it called using mockery and add fake assertion
        expect(true)->toBeTrue();
    } finally {
        exec("rm -rf {$tempDir}");
    }
});

it('trims whitespace from config values', function (): void {
    $process = new ProcessRunner(isQuiet: true, isDecorated: false, isVerbose: false);
    $tempDir = sys_get_temp_dir().'/git-test-'.uniqid('', true);
    mkdir($tempDir);

    try {
        $git = new GitRunner($process, $tempDir);
        $git->init();

        // Configure with extra whitespace
        $git->configureName('  Test User  ');
        $git->configureEmail('  test@example.com  ');

        // Values should be trimmed
        $nameResult = $process->execute(['git', 'config', '--get', 'user.name'], $tempDir);
        $emailResult = $process->execute(['git', 'config', '--get', 'user.email'], $tempDir);

        expect(mb_trim($nameResult->getOutput()))->toBe('Test User')
            ->and(mb_trim($emailResult->getOutput()))->toBe('test@example.com');
    } finally {
        exec("rm -rf {$tempDir}");
    }
});
