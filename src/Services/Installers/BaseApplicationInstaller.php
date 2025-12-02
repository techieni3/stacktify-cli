<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Techieni3\StacktifyCli\Enums\DeveloperTool;
use Techieni3\StacktifyCli\Enums\ToolingPreference;
use Techieni3\StacktifyCli\Installables\PhpstanInstallable;
use Techieni3\StacktifyCli\Installables\PintInstallable;
use Techieni3\StacktifyCli\Installables\RectorInstallable;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;

/**
 * Apply baseline tweaks to a freshly scaffolded Laravel application.
 */
final class BaseApplicationInstaller extends AbstractInstaller
{
    /**
     * The installable for Pint.
     */
    private PintInstallable $pintInstallable;

    /**
     * Run all baseline customizations.
     */
    public function install(): void
    {
        if ($this->config()->getToolingPreference() === ToolingPreference::Skip) {
            return;
        }

        if ( ! in_array(DeveloperTool::Stacktify, $this->config()->getDeveloperTools(), true)) {
            return;
        }

        $this->pintInstallable = new PintInstallable();

        $this->configurePint();
        $this->configureAppServiceProvider();
        $this->configureRector();
        $this->configurePhpstan();
    }

    /**
     * Configure Pint for the project.
     */
    private function configurePint(): void
    {
        // publish pint config
        $this->publishStubs($this->pintInstallable->stubs());

        // add a composer script
        $this->addComposerScripts($this->pintInstallable->composerScripts());

        // run pint for all files
        $this->runCommands($this->pintInstallable->postInstall());

        // commit changes
        $this->commitChanges('chore: configure Pint code formatter');

        $this->notifySuccess('Pint configured successfully');
    }

    /**
     * Configure Rector for the project.
     */
    private function configureRector(): void
    {
        $installable = new RectorInstallable();

        // install rector
        $this->installPackages(devDependencies: $installable->devDependencies());

        // publish pint config
        $this->publishStubs($installable->stubs());

        // add a composer script
        $this->addComposerScripts($installable->composerScripts());

        // run rector for all files
        $this->runCommands($installable->postInstall());

        // run pint for all files
        $this->runCommands($this->pintInstallable->postInstall());

        // commit changes
        $this->commitChanges('chore: configure Rector for code refactoring');

        $this->notifySuccess('Rector configured successfully');
    }

    /**
     * Configure Phpstan & larastan for the project.
     */
    private function configurePhpstan(): void
    {
        $installable = new PhpstanInstallable();

        // install phpstan
        $this->installPackages(devDependencies: $installable->devDependencies());

        // publish phpstan config
        $this->publishStubs($installable->stubs());

        // add a composer script
        $this->addComposerScripts($installable->composerScripts());

        // commit changes
        $this->commitChanges('chore: configure Phpstan for static analysis');

        $this->notifySuccess('Phpstan configured successfully');
    }

    /**
     * Configure the AppServiceProvider with production-ready defaults.
     */
    private function configureAppServiceProvider(): void
    {
        $appServiceProviderPath = $this->paths()->getPath('app/Providers/AppServiceProvider.php');

        $editor = FileEditor::serviceProvider($appServiceProviderPath);

        // Add use statements
        $editor->addUseStatements($this->getAppServiceProviderUseStatements());

        // Add all configuration methods
        $editor->addMethods([
            $this->getConfigureCommandsMethod(),
            $this->getConfigureDatesMethod(),
            $this->getConfigureModelsMethod(),
            $this->getConfigureUrlMethod(),
            $this->getConfigureViteMethod(),
            $this->getConfigureValidationsMethod(),
            $this->getOptimizeTestsMethod(),
        ]);

        // Add boot method calls
        $editor->addToBoot($this->getAppServiceProviderBootCalls());

        $editor->save();

        $this->commitChanges('feat: configure AppServiceProvider with stacktify recommended defaults');

        $this->notifySuccess('AppServiceProvider configured successfully');
    }

    /**
     * Get use statements for AppServiceProvider.
     *
     * @return array<string>
     */
    private function getAppServiceProviderUseStatements(): array
    {
        return [
            'Carbon\CarbonImmutable',
            'Illuminate\Database\Eloquent\Model',
            'Illuminate\Support\Facades\Date',
            'Illuminate\Support\Facades\DB',
            'Illuminate\Support\Facades\Http',
            'Illuminate\Support\Facades\URL',
            'Illuminate\Support\Facades\Vite',
            'Illuminate\Support\Sleep',
            'Illuminate\Validation\Rules\Email',
            'Illuminate\Validation\Rules\Password',
            'Override',
        ];
    }

    /**
     * Get boot method calls for AppServiceProvider.
     *
     * @return array<string>
     */
    private function getAppServiceProviderBootCalls(): array
    {
        return [
            '$this->configureCommands();',
            '$this->configureDates();',
            '$this->configureModels();',
            '$this->configureUrl();',
            '$this->configureVite();',
            '$this->configureValidations();',
            '$this->optimizeTests();',
        ];
    }

    /**
     * Get configureCommands method definition.
     */
    private function getConfigureCommandsMethod(): string
    {
        return <<<'PHP'
    /**
     * Configure the application's commands.
     */
    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
PHP;
    }

    /**
     * Get configureDates method definition.
     */
    private function getConfigureDatesMethod(): string
    {
        return <<<'PHP'
    /**
     * It's recommended to use CarbonImmutable as it's immutable and thread-safe to avoid issues with mutability.
     *
     * @see https://dyrynda.com.au/blog/laravel-immutable-dates
     */
    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }
PHP;
    }

    /**
     * Get configureModels method definition.
     */
    private function getConfigureModelsMethod(): string
    {
        return <<<'PHP'
    /**
     * Configure the application's models.
     *
     * @see https://laravel.com/docs/eloquent#configuring-eloquent-strictness
     */
    private function configureModels(): void
    {
        Model::unguard();

        Model::shouldBeStrict($this->app->isLocal());

        Model::automaticallyEagerLoadRelationships($this->app->isProduction());
    }
PHP;
    }

    /**
     * Get configureUrl method definition.
     */
    private function getConfigureUrlMethod(): string
    {
        return <<<'PHP'
    /**
     * Configure the application's URL.
     *
     * @see https://laravel.com/docs/octane#serving-your-application-via-https
     */
    private function configureUrl(): void
    {
        URL::forceHttps($this->app->isProduction());
    }
PHP;
    }

    /**
     * Get configureVite method definition.
     */
    private function getConfigureViteMethod(): string
    {
        return <<<'PHP'
    /**
     * Configure the application's Vite loading strategy.
     */
    private function configureVite(): void
    {
        Vite::useAggressivePrefetching();
    }
PHP;
    }

    /**
     * Get configureValidations method definition.
     */
    private function getConfigureValidationsMethod(): string
    {
        return <<<'PHP'
    /**
     * Configure validation rules.
     */
    private function configureValidations(): void
    {
        Password::defaults(fn () => $this->app->isProduction() ? Password::min(8)->max(125) : null);
        Email::defaults(fn () => $this->app->isProduction() ? Email::default()->validateMxRecord()->preventSpoofing() : null);
    }
PHP;
    }

    /**
     * Get optimizeTests method definition.
     */
    private function getOptimizeTestsMethod(): string
    {
        return <<<'PHP'
    /**
     * Configure Stray Requests & sleep when running tests.
     */
    private function optimizeTests(): void
    {
        Http::preventStrayRequests($this->app->runningUnitTests());

        Sleep::fake($this->app->runningUnitTests());
    }
PHP;
    }
}
