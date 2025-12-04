<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Techieni3\StacktifyCli\Services\FileEditors\ServiceProviderEditor;

$destinationDirectory = dirname(__DIR__).'/../../Workspace';

beforeEach(function () use ($destinationDirectory): void {
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($destinationDirectory);

    $filesystem->copy(
        dirname(__DIR__).'/../../Fixtures/AppServiceProvider.php',
        $destinationDirectory.'/AppServiceProvider.php'
    );
});

afterEach(function () use ($destinationDirectory): void {
    if (file_exists($destinationDirectory.'/AppServiceProvider.php')) {
        unlink($destinationDirectory.'/AppServiceProvider.php');
    }
});

describe('use statements', function () use ($destinationDirectory): void {
    it('adds a single use statement', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addUseStatements(Gate::class)
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('use Illuminate\Support\Facades\Gate;');
    });

    it('adds multiple use statements as array', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addUseStatements([
            Gate::class,
            'App\Models\User',
        ])
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('use Illuminate\Support\Facades\Gate;')
            ->and($content)->toContain('use App\Models\User;');
    });

    it('avoids duplicate use statements', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addUseStatements(Gate::class)
            ->addUseStatements(Gate::class)
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect(mb_substr_count($content, 'use Illuminate\Support\Facades\Gate;'))->toBe(1);
    });
});

describe('register method', function () use ($destinationDirectory): void {
    it('adds code to register method', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addToRegister('$this->app->singleton(FooService::class);')
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('$this->app->singleton(FooService::class);');
    });

    it('adds multiple statements to register method', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addToRegister([
            '$this->app->singleton(FooService::class);',
            '$this->app->bind(BarInterface::class, BarService::class);',
        ])
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('$this->app->singleton(FooService::class);')
            ->and($content)->toContain('$this->app->bind(BarInterface::class, BarService::class);');
    });
});

describe('boot method', function () use ($destinationDirectory): void {
    it('adds code to boot method', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addToBoot('Gate::define("admin", fn($user) => $user->isAdmin());')
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('Gate::define');
    });

    it('adds multiple statements to boot method', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addToBoot([
            'Model::preventLazyLoading(!$this->app->isProduction());',
            'Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());',
        ])
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('Model::preventLazyLoading')
            ->and($content)->toContain('Model::preventSilentlyDiscardingAttributes');
    });
});

describe('new methods', function () use ($destinationDirectory): void {
    it('adds a single method to the class', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $method = <<<'PHP'
                /**
                 * Configure the application's commands.
                 */
                private function configureCommands(): void
                {
                    DB::prohibitDestructiveCommands($this->app->isProduction());
                }
            PHP;

        $editor->addMethods($method)->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('private function configureCommands(): void')
            ->and($content)->toContain('DB::prohibitDestructiveCommands')
            ->and($content)->toContain("Configure the application's commands");
    });

    it('adds multiple methods as array', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $methods = [
            <<<'PHP'
                    private function configureCommands(): void
                    {
                        DB::prohibitDestructiveCommands($this->app->isProduction());
                    }
                PHP,
            <<<'PHP'
                    private function configureDates(): void
                    {
                        Date::use(CarbonImmutable::class);
                    }
                PHP,
        ];

        $editor->addMethods($methods)->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('private function configureCommands(): void')
            ->and($content)->toContain('DB::prohibitDestructiveCommands')
            ->and($content)->toContain('private function configureDates(): void')
            ->and($content)->toContain('Date::use(CarbonImmutable::class)');
    });

    it('adds method and calls it from boot', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $method = <<<'PHP'
                /**
                 * Configure the application's models.
                 */
                private function configureModels(): void
                {
                    Model::shouldBeStrict($this->app->isLocal());
                }
            PHP;

        $editor->addMethods($method)
            ->addToBoot('$this->configureModels();')
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('private function configureModels(): void')
            ->and($content)->toContain('Model::shouldBeStrict')
            ->and($content)->toContain('$this->configureModels()');
    });
});

describe('fluent interface', function () use ($destinationDirectory): void {
    it('chains multiple operations', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addUseStatements([
            Gate::class,
            'App\Models\User',
        ])
            ->addToRegister('$this->app->singleton(FooService::class);')
            ->addToBoot('Gate::define("admin", fn(User $user) => $user->isAdmin());')
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('use Illuminate\Support\Facades\Gate;')
            ->and($content)->toContain('use App\Models\User;')
            ->and($content)->toContain('$this->app->singleton(FooService::class);')
            ->and($content)->toContain('Gate::define');
    });
});

describe('change tracking', function () use ($destinationDirectory): void {
    it('only saves when changed', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $result = $editor->save();

        expect($result)->toBeFalse();
    });

    it('saves when use statement is added', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addUseStatements(Gate::class);

        $result = $editor->save();

        expect($result)->toBeTrue();
    });

    it('saves when register code is added', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addToRegister('$this->app->singleton(FooService::class);');

        $result = $editor->save();

        expect($result)->toBeTrue();
    });

    it('saves when boot code is added', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addToBoot('Model::preventLazyLoading();');

        $result = $editor->save();

        expect($result)->toBeTrue();
    });

    it('saves when methods are added', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $method = <<<'PHP'
                private function configureCommands(): void
                {
                    DB::prohibitDestructiveCommands($this->app->isProduction());
                }
            PHP;

        $editor->addMethods($method);

        $result = $editor->save();

        expect($result)->toBeTrue();
    });
});

describe('real-world scenarios', function () use ($destinationDirectory): void {
    it('configures model strictness', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addUseStatements('Illuminate\Database\Eloquent\Model')
            ->addToBoot([
                'Model::preventLazyLoading(!$this->app->isProduction());',
                'Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());',
            ])
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('use Illuminate\Database\Eloquent\Model;')
            ->and($content)->toContain('Model::preventLazyLoading')
            ->and($content)->toContain('Model::preventSilentlyDiscardingAttributes');
    });

    it('registers service bindings', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addToRegister([
            '$this->app->singleton(PaymentGateway::class, StripeGateway::class);',
            '$this->app->bind(MailerInterface::class, SendgridMailer::class);',
        ])
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('PaymentGateway::class')
            ->and($content)->toContain('StripeGateway::class')
            ->and($content)->toContain('MailerInterface::class')
            ->and($content)->toContain('SendgridMailer::class');
    });

    it('configures gates and policies', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $editor->addUseStatements([
            Gate::class,
            'App\Models\User',
        ])
            ->addToBoot([
                'Gate::define("viewAdmin", fn(User $user) => $user->isAdmin());',
                'Gate::define("manageUsers", fn(User $user) => $user->isSuperAdmin());',
            ])
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        expect($content)->toContain('use Illuminate\Support\Facades\Gate;')
            ->and($content)->toContain('use App\Models\User;')
            ->and($content)->toContain('Gate::define("viewAdmin"')
            ->and($content)->toContain('Gate::define("manageUsers"');
    });

    it('organizes boot logic into private methods', function () use ($destinationDirectory): void {
        $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

        $configureCommandsMethod = <<<'PHP'
                /**
                 * Configure the application's commands.
                 */
                private function configureCommands(): void
                {
                    DB::prohibitDestructiveCommands($this->app->isProduction());
                }
            PHP;

        $configureModelsMethod = <<<'PHP'
                /**
                 * Configure the application's models.
                 */
                private function configureModels(): void
                {
                    Model::shouldBeStrict($this->app->isLocal());
                    Model::unguard();
                }
            PHP;

        $editor->addUseStatements([
            DB::class,
            'Illuminate\Database\Eloquent\Model',
        ])
            ->addMethods([$configureCommandsMethod, $configureModelsMethod])
            ->addToBoot([
                '$this->configureCommands();',
                '$this->configureModels();',
            ])
            ->save();

        $content = file_get_contents($destinationDirectory.'/AppServiceProvider.php');

        // Check use statements
        expect($content)->toContain('use Illuminate\Support\Facades\DB;')
            ->and($content)->toContain('use Illuminate\Database\Eloquent\Model;')
            // Check methods exist
            ->and($content)->toContain('private function configureCommands(): void')
            ->and($content)->toContain('private function configureModels(): void')
            // Check method bodies
            ->and($content)->toContain('DB::prohibitDestructiveCommands')
            ->and($content)->toContain('Model::shouldBeStrict')
            ->and($content)->toContain('Model::unguard()')
            // Check methods are called from boot
            ->and($content)->toContain('$this->configureCommands()')
            ->and($content)->toContain('$this->configureModels()');
    });
});

it('properly write AppServiceProvider file', function () use ($destinationDirectory): void {
    $editor = new ServiceProviderEditor($destinationDirectory.'/AppServiceProvider.php');

    $editor->addUseStatements('Illuminate\Database\Eloquent\Model')
        ->addToBoot([
            'Model::preventLazyLoading(!$this->app->isProduction());',
            'Model::preventSilentlyDiscardingAttributes(!$this->app->isProduction());',
        ])
        ->save();

    // Verify the file has valid PHP syntax
    $result = exec(
        command: 'php -l '.escapeshellarg($destinationDirectory.'/AppServiceProvider.php').' 2>&1',
        output: $output,
        result_code: $returnCode
    );

    expect($returnCode)->toBe(0)
        ->and($result)->toContain('No syntax errors detected');
});
