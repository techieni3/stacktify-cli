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
use Techieni3\StacktifyCli\Exceptions\GitNotAvailable;
use Techieni3\StacktifyCli\Services\ApplicationValidator;
use Techieni3\StacktifyCli\Services\AppUrlGenerator;
use Techieni3\StacktifyCli\Services\Composer;
use Techieni3\StacktifyCli\Services\ConsoleNotifier;
use Techieni3\StacktifyCli\Services\DatabaseConfigurator;
use Techieni3\StacktifyCli\Services\ExecutableLocator;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\Git\GitRunner;
use Techieni3\StacktifyCli\Services\Git\NullGitRunner;
use Techieni3\StacktifyCli\Services\Installers\BaseApplicationInstaller;
use Techieni3\StacktifyCli\Services\Installers\DeveloperToolsInstaller;
use Techieni3\StacktifyCli\Services\Installers\InstallerContext;
use Techieni3\StacktifyCli\Services\Installers\TestingFrameworkInstaller;
use Techieni3\StacktifyCli\Services\NodePackageManagerRunner;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Services\ProcessRunner;
use Techieni3\StacktifyCli\Services\SelectionPresenter;
use Techieni3\StacktifyCli\Traits\CollectsScaffoldInputs;
use Techieni3\StacktifyCli\Traits\ConfiguresLaravelPrompts;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

#[AsCommand(name: 'new', description: 'Create a new Laravel application')]
final class NewCommand extends Command
{
    use CollectsScaffoldInputs;
    use ConfiguresLaravelPrompts;

    /**
     * The input interface implementation.
     */
    private InputInterface $input;

    /**
     * The Symfony style instance.
     */
    private SymfonyStyle $io;

    /**
     * The Git client implementation.
     */
    private GitClient $git;

    /**
     * The Composer instance.
     */
    private Composer $composer;

    /**
     * The scaffold configuration instance.
     */
    private ScaffoldConfig $config;

    /**
     * The path resolver instance.
     */
    private PathResolver $paths;

    /**
     * The path to the PHP executable.
     */
    private string $php;

    /**
     * The console notifier instance.
     */
    private ConsoleNotifier $notifier;

    /**
     * Create a new command instance.
     */
    public function __construct(?string $name = null)
    {
        $this->config = new ScaffoldConfig();

        parent::__construct($name);
    }

    /**
     * Configure the command's arguments and options.
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
                description: 'Skip all Git operations (init, commit, branch rename). Default: perform Git actions'
            ),
            new InputOption(
                name: 'force',
                shortcut: '-f',
                mode: InputOption::VALUE_NONE,
                description: 'Forces install even if the directory already exists'
            ),
        ]));
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);
        $this->notifier = new ConsoleNotifier($output);

        if ( ! $input->isInteractive()) {
            $this->prepareNonInteractiveConfiguration();
        }
    }

    /**
     * Interact with the user to gather input.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->configurePrompts($input, $output);

        $this->displayWelcomeMessage($output);

        $this->collectScaffoldInputs();
    }

    /**
     * Execute the console command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->paths = new PathResolver($this->config->getName());
        $this->php = new ExecutableLocator()->findPhp();

        $presenter = new SelectionPresenter($this->config, $this->paths);
        $confirmed = $presenter->reviewAndConfirm($input, $this->io);

        if ( ! $confirmed) {
            return Command::FAILURE;
        }

        $validator = new ApplicationValidator();
        $validator->ensureRequiredExtensionsAreAvailable();

        $directory = $this->paths->getInstallationDirectory();

        if ( ! $input->getOption('force')) {
            $validator->ensureApplicationDoesNotExist($directory);
        }

        $validator->ensureForceNotUsedWithCurrentDirectory($directory, (bool) $input->getOption('force'));

        $process = new ProcessRunner(
            isQuiet: (bool) $this->input->getOption('quiet'),
            isDecorated: $output->isDecorated(),
            isVerbose: $output->isVerbose()
        );

        $nodePackageManager = new NodePackageManagerRunner(
            packageManager: $this->config->getPackageManager(),
            process: $process,
            cwd: $this->paths->getInstallationDirectory(),
        );

        $this->composer = new Composer($process, $directory);

        $projectCreation = $process->runCommands(
            commands: $this->getInstallCommands($directory, $input),
            description: 'Creating Laravel application...'
        );

        if ( ! $projectCreation->isSuccessful()) {
            $this->io->error('Failed to create the application');
            $this->io->error($projectCreation->getErrorOutput());

            return Command::FAILURE;
        }

        // update dependencies to latest version
        $this->composer->updateDependencies();

        $this->notifier->success('Application created successfully');

        $this->setAppUrlInEnv();

        $databaseConfigurator = new DatabaseConfigurator($this->config, $this->paths, $this->php);

        $databaseConfigurator->configureDatabaseConnection();
        $databaseConfigurator->runMigration($process, $this->input->isInteractive());

        $this->setUpGitRepository($process, $directory);

        if ($this->input->isInteractive()) {
            $context = new InstallerContext(
                process: $process,
                composer: $this->composer,
                nodePackageManager: $nodePackageManager,
                config: $this->config,
                paths: $this->paths,
                git: $this->git,
                notifier: $this->notifier,
            );
            // Install testing framework
            new TestingFrameworkInstaller($context)->install();

            // Install Stacktify Recommendations
            new BaseApplicationInstaller($context)->install();

            // Install developer tools
            new DeveloperToolsInstaller($context)->install();
        }

        // build assets
        $nodePackageManager->build();

        $this->io->newLine();

        $this->io->success('Successfully created new application');

        return Command::SUCCESS;
    }

    /**
     * Display the welcome message.
     */
    private function displayWelcomeMessage(OutputInterface $output): void
    {
        $output->write('<fg=red>
 ███████╗ ████████╗  █████╗   ██████╗ ██╗  ██╗ ████████╗ ██╗ ███████╗ ██╗   ██╗
 ██╔════╝ ╚══██╔══╝ ██╔══██╗ ██╔════╝ ██║ ██╔╝ ╚══██╔══╝ ██║ ██╔════╝ ╚██╗ ██╔╝
 ███████╗    ██║    ███████║ ██║      █████╔╝     ██║    ██║ █████╗    ╚████╔╝
 ╚════██║    ██║    ██╔══██║ ██║      ██╔═██╗     ██║    ██║ ██╔══╝     ╚██╔╝
 ███████║    ██║    ██║  ██║ ╚██████╗ ██║  ██╗    ██║    ██║ ██║         ██║
 ╚══════╝    ╚═╝    ╚═╝  ╚═╝  ╚═════╝ ╚═╝  ╚═╝    ╚═╝    ╚═╝ ╚═╝         ╚═╝
        </>'.PHP_EOL);
    }

    /**
     * Get the commands to install the application.
     *
     * Builds a list of shell commands to:
     * 1. Create Laravel project via Composer (with --remove-vcs, --prefer-dist, --no-scripts)
     * 2. Run post-installation scripts (creates .env from .env.example)
     * 3. Generate application key (required for encryption)
     * 4. Make artisan executable on Unix systems (chmod 755)
     *
     * @param  string  $directory  The installation directory path
     * @param  InputInterface  $input  Console input for reading options
     * @return list<string> Array of commands to execute sequentially
     */
    private function getInstallCommands(string $directory, InputInterface $input): array
    {
        $composer = $this->composer->path();
        $phpBinary = $this->php;
        $version = $this->config->getVersion();

        $commands = [
            // Create Laravel project with specific flags:
            // --remove-vcs: Remove Git from Laravel skeleton (we'll init our own)
            // --prefer-dist: Download distribution packages (faster)
            // --no-scripts: Skip auto-running scripts (we run them manually next)
            $composer." create-project laravel/laravel \"{$directory}\" {$version} --remove-vcs --prefer-dist --no-scripts",

            // Run post-installation script to create .env file
            $composer." run post-root-package-install -d \"{$directory}\"",

            // Generate unique application key for encryption
            $phpBinary." \"{$directory}/artisan\" key:generate --ansi",
        ];

        // Prepend force removal command if --force flag is used
        // Note: This is destructive and will delete the existing directory
        if ($directory !== '.' && $input->getOption('force')) {
            $commands = [...$this->getForceInstallCommand($directory), ...$commands];
        }

        // Make artisan executable on Unix-like systems
        if (PHP_OS_FAMILY !== 'Windows') {
            $commands[] = "chmod 755 \"{$directory}/artisan\"";
        }

        return $commands;
    }

    /**
     * Get the command to force install the application.
     *
     * Returns platform-specific command to remove an existing directory.
     * This is a destructive operation that permanently deletes all directory contents.
     *
     * Windows: Uses 'rd /s /q' (remove directory, subdirectories, quiet mode)
     * Unix/Linux/macOS: Uses 'rm -rf' (remove recursive, force)
     *
     * @param  string  $directory  The directory path to remove
     * @return list<string> Single-element array containing the removal command
     */
    private function getForceInstallCommand(string $directory): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: rd /s (remove subdirectories) /q (quiet mode, no confirmation)
            return ["(if exist \"{$directory}\" rd /s /q \"{$directory}\")"];
        }

        // Unix/Linux/macOS: rm -rf (recursive, force without prompt)
        return ["rm -rf \"{$directory}\""];
    }

    /**
     * Set up the Git repository.
     */
    private function setUpGitRepository(ProcessRunner $process, string $directory): void
    {
        $gitEnabled = ! $this->input->getOption('no-git');
        $interactive = ! $this->input->getOption('no-interaction');

        if ($gitEnabled) {
            try {
                $git = new GitRunner($process, $directory);
                $git->ensureAvailable();
            } catch (GitNotAvailable) {
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

        $git->createInitialCommit();

        $this->notifier->success('Git repository initialized');

        $this->git = $git;
    }

    /**
     * Set the APP_URL in the .env file.
     */
    private function setAppUrlInEnv(): void
    {
        $url = new AppUrlGenerator($this->config->getName())->generate();

        FileEditor::replaceInFile(
            filePath: $this->paths->getEnvPath(),
            replacement: new Replacement(
                search: 'APP_URL=http://localhost',
                replace: 'APP_URL='.$url,
            )
        );
    }
}
