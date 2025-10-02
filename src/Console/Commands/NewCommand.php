<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Console\Commands;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Techieni3\StacktifyCli\Config\ScaffoldConfig;
use Techieni3\StacktifyCli\Contracts\GitClient;
use Techieni3\StacktifyCli\Enums\Authentication;
use Techieni3\StacktifyCli\Enums\Database;
use Techieni3\StacktifyCli\Enums\Frontend;
use Techieni3\StacktifyCli\Enums\TestingFramework;
use Techieni3\StacktifyCli\Exceptions\GitNotAvailable;
use Techieni3\StacktifyCli\Support\Composer;
use Techieni3\StacktifyCli\Support\DatabaseConfigurator;
use Techieni3\StacktifyCli\Support\FileEditor;
use Techieni3\StacktifyCli\Support\GitRunner;
use Techieni3\StacktifyCli\Support\NullGitRunner;
use Techieni3\StacktifyCli\Support\ProcessRunner;
use Techieni3\StacktifyCli\Traits\CollectsScaffoldInputs;
use Techieni3\StacktifyCli\Traits\ConfiguresLaravelPrompts;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

#[AsCommand(name: 'new', description: 'Create a new Laravel application')]
final class NewCommand extends Command
{
    use CollectsScaffoldInputs;
    use ConfiguresLaravelPrompts;

    private OutputInterface $output;

    private InputInterface $input;

    private SymfonyStyle $io;

    private GitClient $git;

    private Frontend $frontend;

    private Database $database;

    private Authentication $authentication;

    private TestingFramework $testingFramework;

    private Composer $composer;

    private ScaffoldConfig $config;

    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this->setDefinition(new InputDefinition([
            new InputArgument('name', InputArgument::REQUIRED, 'Name of the new application'),
            // options
            new InputOption(
                name: 'dev',
                mode: InputOption::VALUE_NONE,
                description: 'Install the latest "development" release'
            ),
            new InputOption(
                name: 'no-git',
                mode: InputOption::VALUE_NONE,
                description: ' Skip all Git operations (init, commit, branch rename). Default: perform Git actions'
            ),
            new InputOption(
                name: 'force',
                shortcut: '-f',
                mode: InputOption::VALUE_NONE,
                description: 'Forces install even if the directory already exists'
            ),
        ]));
    }

    /**
     * Interact with the user.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->configurePrompts($input, $output);

        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $this->displayWelcomeMessage($output);

        $this->collectScaffoldInputs();
    }

    /**
     * Execute the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $confirmed = $this->reviewAndConfirm();

        if ( ! $confirmed) {
            return Command::FAILURE;
        }

        $this->ensureExtensionsAreAvailable();

        $directory = $this->config->getInstallationDirectory();

        if ( ! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        if ($directory === '.' && $input->getOption('force')) {
            throw new RuntimeException('Cannot use --force option when using current directory for installation!');
        }

        $process = new ProcessRunner(
            isQuiet: (bool) $this->input->getOption('quiet'),
            isDecorated: $this->output->isDecorated()
        );

        $this->composer = new Composer($process, $directory);

        $projectCreation = $process->runCommands($this->getInstallCommands($directory, $input));

        if ( ! $projectCreation->isSuccessful()) {
            $this->io->error('Failed to create the application');
            $this->io->error($projectCreation->getErrorOutput());

            return Command::FAILURE;
        }

        $this->setAppUrlInEnv();

        $databaseConfigurator = new DatabaseConfigurator($this->config);

        $databaseConfigurator->configureDatabaseConnection();
        $databaseConfigurator->runMigration($process);

        $this->setUpGitRepository($process, $directory);

        $this->io->success('Successfully created new application');

        return Command::SUCCESS;
    }

    private function displayWelcomeMessage(OutputInterface $output): void
    {
        $output->write('<fg=red>
 ███████╗ ████████╗  █████╗   ██████╗ ██╗  ██╗ ████████╗ ██╗███████╗ ██╗   ██╗
 ██╔════╝ ╚══██╔══╝ ██╔══██╗ ██╔════╝ ██║ ██╔╝ ╚══██╔══╝ ██║██╔════╝ ╚██╗ ██╔╝
 ███████╗    ██║    ███████║ ██║      █████╔╝     ██║    ██║█████╗    ╚████╔╝
 ╚════██║    ██║    ██╔══██║ ██║      ██╔═██╗     ██║    ██║██╔══╝     ╚██╔╝
 ███████║    ██║    ██║  ██║ ╚██████╗ ██║  ██╗    ██║    ██║██║         ██║
 ╚══════╝    ╚═╝    ╚═╝  ╚═╝  ╚═════╝ ╚═╝  ╚═╝    ╚═╝    ╚═╝╚═╝         ╚═╝
        </>'.PHP_EOL);
    }

    private function ensureExtensionsAreAvailable(): void
    {
        $availableExtensions = get_loaded_extensions();

        $requiredExtensions = [
            'ctype',
            'filter',
            'hash',
            'mbstring',
            'openssl',
            'session',
            'tokenizer',
        ];

        $missingExtensions = array_filter(
            $requiredExtensions,
            static fn ($extension) => ! in_array($extension, $availableExtensions)
        );

        if ($missingExtensions === []) {
            return;
        }

        throw new RuntimeException(
            sprintf('The following PHP extensions are required but are not installed: %s', implode(', ', $missingExtensions))
        );
    }

    private function getInstallCommands(string $directory, InputInterface $input): array
    {
        $composer = $this->composer->getComposer();
        $phpBinary = $this->config->getPhpBinary();
        $version = $this->config->getVersion();

        $commands = [
            $composer." create-project laravel/laravel \"{$directory}\" {$version} --remove-vcs --prefer-dist --no-scripts",
            $composer." run post-root-package-install -d \"{$directory}\"",
            $phpBinary." \"{$directory}/artisan\" key:generate --ansi",
        ];

        if ($directory !== '.' && $input->getOption('force')) {
            $commands = array_merge($this->getForceInstallCommand($directory), $commands);
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            $commands[] = "chmod 755 \"{$directory}/artisan\"";
        }

        return $commands;
    }

    private function getForceInstallCommand(string $directory): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return ["(if exist \"{$directory}\" rd /s /q \"{$directory}\")"];
        }

        return ["rm -rf \"{$directory}\""];
    }

    private function setUpGitRepository(ProcessRunner $process, string $directory): void
    {
        $gitEnabled = ! $this->input->getOption('no-git');
        $interactive = ! $this->input->getOption('no-interaction');

        if ($gitEnabled) {
            try {
                $git = new GitRunner($process, $directory);
                $git->ensureAvailable();
            } catch (GitNotAvailable $e) {
                $this->io->warning('Git not available; continuing without a repository.');
                $git = new NullGitRunner();
            } catch (RuntimeException $e) {
                $this->io->error($e->getMessage());
                $git = new NullGitRunner();
            }
        } else {
            $git = new NullGitRunner();
        }

        $git->init();

        if ($git instanceof GitRunner && ! $git->hasIdentityConfigured()) {
            if ($interactive) {
                $this->io->note('Configuring Git identity (user.name / user.email) for this repository.');

                $git->configureName($this->askGitUserName());
                $git->configureEmail($this->askGitEmail());
            } else {
                $this->io->warning('Git identity missing and non-interactive mode enabled; skipping Git setup.');
                $git = new NullGitRunner();
            }
        }

        $git->initializeRepository();

        $this->git = $git;
    }

    private function setAppUrlInEnv(): void
    {
        $envFile = FileEditor::init($this->config->getEnvFilePath());

        $envFile->queueReplacement(
            new Replacement(
                search: 'APP_URL=http://localhost',
                replace: 'APP_URL='.$this->config->getAppUrl(),
            )
        );

        $envFile->applyReplacements();
    }

    /**
     * Verify that the application does not already exist.
     */
    private function verifyApplicationDoesntExist(string $directory): void
    {
        if ($directory !== getcwd() && (is_dir($directory) || is_file($directory))) {
            throw new RuntimeException('Application already exists!');
        }
    }
}
