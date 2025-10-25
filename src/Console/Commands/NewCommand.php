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
use Techieni3\StacktifyCli\Services\AppUrlGenerator;
use Techieni3\StacktifyCli\Services\Composer;
use Techieni3\StacktifyCli\Services\DatabaseConfigurator;
use Techieni3\StacktifyCli\Services\ExecutableLocator;
use Techieni3\StacktifyCli\Services\FileEditors\FileEditor;
use Techieni3\StacktifyCli\Services\Git\GitRunner;
use Techieni3\StacktifyCli\Services\Git\NullGitRunner;
use Techieni3\StacktifyCli\Services\Installers\BaseApplicationInstaller;
use Techieni3\StacktifyCli\Services\Installers\DeveloperToolsInstaller;
use Techieni3\StacktifyCli\Services\Installers\InstallerContext;
use Techieni3\StacktifyCli\Services\Installers\TestingFrameworkInstaller;
use Techieni3\StacktifyCli\Services\PathResolver;
use Techieni3\StacktifyCli\Services\ProcessRunner;
use Techieni3\StacktifyCli\Traits\CollectsScaffoldInputs;
use Techieni3\StacktifyCli\Traits\ConfiguresLaravelPrompts;
use Techieni3\StacktifyCli\ValueObjects\Replacements\Replacement;

#[AsCommand(name: 'new', description: 'Create a new Laravel application')]
final class NewCommand extends Command
{
    use CollectsScaffoldInputs;
    use ConfiguresLaravelPrompts;

    /**
     * The output interface implementation.
     */
    private OutputInterface $output;

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

    private string $php;

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
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

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

        $confirmed = $this->reviewAndConfirm();

        if ( ! $confirmed) {
            return Command::FAILURE;
        }

        $this->ensureExtensionsAreAvailable();

        $directory = $this->paths->getInstallationDirectory();

        if ( ! $input->getOption('force')) {
            $this->verifyApplicationDoesntExist($directory);
        }

        if ($directory === '.' && $input->getOption('force')) {
            throw new RuntimeException('Cannot use --force option when using current directory for installation!');
        }

        $process = new ProcessRunner(
            isQuiet: (bool) $this->input->getOption('quiet'),
            isDecorated: $this->output->isDecorated(),
            isVerbose: $this->output->isVerbose()
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

        $this->success('Application created successfully');

        $this->setAppUrlInEnv();

        $databaseConfigurator = new DatabaseConfigurator($this->config, $this->paths, $this->php);

        $databaseConfigurator->configureDatabaseConnection();
        $databaseConfigurator->runMigration($process, $this->input->isInteractive());

        $this->setUpGitRepository($process, $directory);

        if ($this->input->isInteractive()) {
            $context = new InstallerContext(
                process: $process,
                composer: $this->composer,
                config: $this->config,
                paths: $this->paths,
                git: $this->git,
            );
            // Install testing framework
            new TestingFrameworkInstaller($context)->install();

            // Install Stacktify Recommendations
            new BaseApplicationInstaller($context)->install();

            // Install developer tools
            new DeveloperToolsInstaller($context)->install();
        }

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
     * Ensure all required PHP extensions are available.
     *
     * @throws RuntimeException
     */
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
            static fn (string $extension): bool => ! in_array($extension, $availableExtensions)
        );

        if ($missingExtensions === []) {
            return;
        }

        throw new RuntimeException(
            sprintf('The following PHP extensions are required but are not installed: %s', implode(', ', $missingExtensions))
        );
    }

    /**
     * Get the commands to install the application.
     *
     * @return list<string>
     */
    private function getInstallCommands(string $directory, InputInterface $input): array
    {
        $composer = $this->composer->path();
        $phpBinary = $this->php;
        $version = $this->config->getVersion();

        $commands = [
            $composer." create-project laravel/laravel \"{$directory}\" {$version} --remove-vcs --prefer-dist --no-scripts",
            $composer." run post-root-package-install -d \"{$directory}\"",
            $phpBinary." \"{$directory}/artisan\" key:generate --ansi",
        ];

        if ($directory !== '.' && $input->getOption('force')) {
            $commands = [...$this->getForceInstallCommand($directory), ...$commands];
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            $commands[] = "chmod 755 \"{$directory}/artisan\"";
        }

        return $commands;
    }

    /**
     * Get the command to force install the application.
     *
     * @return list<string>
     */
    private function getForceInstallCommand(string $directory): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return ["(if exist \"{$directory}\" rd /s /q \"{$directory}\")"];
        }

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

        $this->success('Git repository initialized');

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

    /**
     * Verify that the application does not already exist.
     *
     * @throws RuntimeException
     */
    private function verifyApplicationDoesntExist(string $directory): void
    {
        if ($directory !== getcwd() && (is_dir($directory) || is_file($directory))) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Write a formatted success message to the output.
     */
    private function success(string $message): void
    {
        $this->output->writeln(sprintf('<info> ✅ </info> %s', $message));
    }
}
