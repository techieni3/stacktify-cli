<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Services\Installers;

use Exception;
use RuntimeException;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Services\Composer;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\NodePackageManagerRunner;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Services\ProcessRunner;
use Techieni3\StacktifyCli\ValueObjects\Script;

use function is_bool;
use function sprintf;

/**
 * Base class for installers.
 */
abstract class AbstractInstaller
{
    public function __construct(protected InstallerContext $context) {}

    /**
     * Install the service.
     */
    abstract public function install(): void;

    /**
     * Get the process runner.
     */
    protected function process(): ProcessRunner
    {
        return $this->context->processRunner();
    }

    /**
     * Get the composer instance.
     */
    protected function composer(): Composer
    {
        return $this->context->composer();
    }

    /**
     * Get the scaffold config.
     */
    protected function config(): ScaffoldConfig
    {
        return $this->context->config();
    }

    /**
     * Get the path resolver.
     */
    protected function paths(): PathResolver
    {
        return $this->context->paths();
    }

    /**
     * Get the git client.
     */
    protected function git(): GitClient
    {
        return $this->context->git();
    }

    /**
     * Get the PHP binary path.
     */
    protected function php(): string
    {
        return $this->context->phpBinary();
    }

    /**
     * Get the node package manager runner.
     */
    protected function node(): NodePackageManagerRunner
    {
        return $this->context->nodePackageManager();
    }

    /**
     * Display a success message.
     */
    protected function notifySuccess(string $message): void
    {
        $this->context->notifier()->success($message);
    }

    /**
     * Install composer dependencies.
     *
     * @param  array<int, string>  $dependencies
     * @param  array<int, string>  $devDependencies
     */
    protected function installPackages(array $dependencies = [], array $devDependencies = []): void
    {
        if ($dependencies !== []) {
            $this->composer()->installDependencies($dependencies);
        }

        if ($devDependencies !== []) {
            $this->composer()->installDevDependencies($devDependencies);
        }
    }

    /**
     * Install NPM dependencies.
     *
     * @param  array<int, string>  $dependencies
     * @param  array<int, string>  $devDependencies
     */
    protected function installNpmPackages(array $dependencies = [], array $devDependencies = []): void
    {
        if ($dependencies !== []) {
            $this->node()->installDependencies($dependencies);
        }

        if ($devDependencies !== []) {
            $this->node()->installDevDependencies($devDependencies);
        }
    }

    /**
     * Publish stub files to the project.
     *
     * @param  array<string, string>  $stubs
     */
    protected function publishStubs(array $stubs): void
    {
        foreach ($stubs as $source => $destination) {
            $destinationPath = $this->paths()->getInstallationDirectory().DIRECTORY_SEPARATOR.$destination;
            FileEditor::copyFile($source, $destinationPath);
        }
    }

    /**
     * Add scripts to the composer.json file.
     *
     * @param  array<Script>  $scripts
     */
    protected function addComposerScripts(array $scripts): void
    {
        if ($scripts === []) {
            return;
        }

        try {
            $composerJson = FileEditor::json($this->paths()->getInstallationDirectory().DIRECTORY_SEPARATOR.'composer.json');
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to read composer.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }

        foreach ($scripts as $script) {
            $composerJson->addScript($script);
        }

        try {
            $composerJson->save();
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to save composer.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }
    }

    /**
     * Append scripts to the composer.json file.
     *
     * @param  array<string, string|array<string>>  $scripts
     */
    protected function appendComposerScripts(array $scripts): void
    {
        if ($scripts === []) {
            return;
        }

        try {
            $composerJson = FileEditor::json($this->paths()->getInstallationDirectory().DIRECTORY_SEPARATOR.'composer.json');
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to read composer.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }

        foreach ($scripts as $name => $command) {
            $composerJson->appendToScript($name, $command);
        }

        try {
            $composerJson->save();
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to save composer.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }
    }

    /**
     * Add scripts to the package.json file.
     *
     * @param  array<Script>  $scripts
     */
    protected function addNpmScripts(array $scripts): void
    {
        if ($scripts === []) {
            return;
        }

        try {
            $packageJson = FileEditor::json($this->paths()->getInstallationDirectory().DIRECTORY_SEPARATOR.'package.json');
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to read package.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }

        foreach ($scripts as $script) {
            $packageJson->addScript($script);
        }

        try {
            $packageJson->save();
        } catch (Exception $exception) {
            throw new RuntimeException("Unable to save package.json: {$exception->getMessage()}", $exception->getCode(), $exception);
        }
    }

    /**
     * Add environment variables to .env & .env.example file.
     *
     * @param  array<string, string|bool>  $variables
     */
    protected function addEnvironmentVariables(array $variables): void
    {
        if ($variables === []) {
            return;
        }

        $env = FileEditor::env($this->paths()->getEnvPath());
        $envExample = FileEditor::env($this->paths()->getEnvExamplePath());

        foreach ($variables as $key => $value) {
            if (is_bool($value)) {
                $env->setBoolean($key, $value);
                $envExample->setBoolean($key, $value);
            } else {
                $env->set($key, $value);
                $envExample->set($key, $value);
            }

        }

        $env->emptyLine();
        $env->save();

        $envExample->emptyLine();
        $envExample->save();
    }

    /**
     * Add configs to a specified file.
     *
     * @param  array<string, string|bool|array|callable>  $configs
     */
    protected function addConfigs(string $file, array $configs): void
    {
        if ($file === '' && $configs !== []) {
            throw new RuntimeException('Config file name cannot be empty.');
        }

        $config = FileEditor::config($this->paths()->getPath('config/'.$file));

        foreach ($configs as $name => $value) {
            $config->set($name, $value);
        }

        $config->save();
    }

    /**
     * Run commands after tool installation.
     *
     * @param  array<string>  $commands
     */
    protected function runCommands(array $commands): void
    {
        foreach ($commands as $command) {
            if (str_starts_with($command, 'composer run')) {
                $this->composer()->runScript($command);

                continue;
            }

            $this->process()->runCommands(
                commands: [$command],
                workingPath: $this->paths()->getInstallationDirectory(),
                description: sprintf('Running %s', $command)
            );
        }
    }

    /**
     * Configure the AppServiceProvider with the provided configuration.
     *
     * @param  array{
     *     useStatements: array<string>,
     *     register: array<string>,
     *     boot: array<string>,
     *     newMethods: array<string>
     * }  $config
     */
    protected function configureServiceProvider(array $config): void
    {
        if ($config['useStatements'] === [] &&
            $config['register'] === [] &&
            $config['boot'] === [] &&
            $config['newMethods'] === []
        ) {
            return;
        }

        $serviceProviderPath = $this->paths()->getPath('app/Providers/AppServiceProvider.php');

        $editor = FileEditor::serviceProvider($serviceProviderPath);

        if ($config['useStatements'] !== []) {
            $editor->addUseStatements($config['useStatements']);
        }

        if ($config['register'] !== []) {
            $editor->addToRegister($config['register']);
        }

        if ($config['boot'] !== []) {
            $editor->addToBoot($config['boot']);
        }

        if ($config['newMethods'] !== []) {
            $editor->addMethods($config['newMethods']);
        }

        $editor->save();
    }

    /**
     * Commit changes.
     */
    protected function commitChanges(string $message): void
    {
        $this->git()->addAll();
        $this->git()->commit($message);
    }
}
