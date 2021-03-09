<?php namespace Winter\Cli\Commands\Install;

use DirectoryIterator;
use Exception;
use RuntimeException;
use Winter\Cli\BaseCommand;
use Winter\Cli\GitHub\Token;
use Winter\Cli\Traits\CheckboxList;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Install command
 *
 * @since 0.3.0
 * @author Ben Thomson
 */
class Command extends BaseCommand
{
    use CheckboxList;

    /** @inheritDoc */
    protected static $defaultName = 'install:run';

    /** @var GitHub\Client GitHub Client instance */
    protected $github = null;

    /** @var string `git` command path */
    protected $gitPath = null;

    /** @var string `composer` command path */
    protected $composerPath = null;

    /** @var string Application name */
    protected $appName = 'Winter CMS';

    /** @var string Application URL */
    protected $appUrl = 'https://localhost/';

    /** @var string Database type */
    protected $dbType = 'mysql';

    /** @var string Database host */
    protected $dbHost = null;

    /** @var int Database port */
    protected $dbPort = null;

    /** @var string Database user*/
    protected $dbUser = null;

    /** @var string Database password */
    protected $dbPass = null;

    /** @var string Database name (or storage location) */
    protected $dbName = 'winter';

    /** @var string Administrator account username */
    protected $adminUsername = 'admin';

    /** @var string Administrator account password */
    protected $adminPassword = null;

    /** @var string Administrator account email address */
    protected $adminEmail = null;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // aliases
            ->setAliases(['install'])
            // the short description shown while running "php bin/console list"
            ->setDescription('Installs Winter CMS.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'This command allows you to install Winter CMS into a specified path.' . PHP_EOL . PHP_EOL .
                'Three types of installation are available:' . PHP_EOL .
                ' - Easy install: Installs Winter CMS using the Winter CMS marketplace.' . PHP_EOL .
                ' - Composer install: Installs Winter CMS using Composer.' . PHP_EOL .
                ' - Contributor install: Installs Winter CMS using Composer, and sets up the installation in order' .
                ' to allow the user to contribute to Winter CMS, including setting up a fork in GitHub and' .
                ' configuring a local Git repository.'
            )

            // arguments
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The path to the Winter CMS project.'
            )

            // options
            ->addOption(
                'composer',
                'c',
                InputOption::VALUE_NONE,
                'Installs Winter CMS using Composer.'
            )
            ->addOption(
                'contributor',
                null,
                InputOption::VALUE_NONE,
                'Installs Winter CMS using Composer, and sets up the installation in order' .
                ' to allow the user to contribute to Winter CMS.'
            )
            ->addOption(
                'easy',
                'e',
                InputOption::VALUE_NONE,
                'Installs Winter CMS using the Winter CMS marketplace.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forces the installation of Winter CMS (will delete any files within the path specified).'
            )
            // settings options
            ->addOption(
                'app-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the name of the Winter CMS project.'
            )
            ->addOption(
                'app-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the URL of the Winter CMS project.'
            )
            ->addOption(
                'db-type',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the database type.'
            )
            ->addOption(
                'db-host',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the database host.'
            )
            ->addOption(
                'db-port',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the database port.'
            )
            ->addOption(
                'db-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the database name (or storage path).'
            )
            ->addOption(
                'db-user',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the database username.'
            )
            ->addOption(
                'db-pass',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the database password.'
            )
            ->addOption(
                'admin-user',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the administrator account username.'
            )
            ->addOption(
                'admin-pass',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the administrator account password.'
            )
            ->addOption(
                'admin-email',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the administrator account email address.'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $askHelper = $this->getHelper('question');

        // Determine installation mode
        if ($input->getOption('composer')) {
            $mode = 'composer';
        } elseif ($input->getOption('contributor')) {
            $mode = 'contributor';
        } elseif ($input->getOption('easy')) {
            $mode = 'easy';
        } else {
            $question = new ChoiceQuestion(
                PHP_EOL .
                '<comment>Please select the installation type.</comment>' . PHP_EOL . PHP_EOL .
                ' - <bold>Easy install (easy):</bold> (default) Installs Winter CMS using the Winter CMS' .
                ' marketplace.' . PHP_EOL .
                ' - <bold>Composer install (composer):</bold> Installs Winter CMS using Composer.' . PHP_EOL .
                ' - <bold>Contributor install (contributor):</bold> Installs Winter CMS using Composer, and sets up' .
                ' the installation in order to allow the user to contribute to Winter CMS, including setting up a' .
                ' fork in GitHub and configuring a local Git repository.' . PHP_EOL,
                ['easy', 'composer', 'contributor'],
                'easy'
            );
            $mode = $askHelper->ask($input, $output, $question);
        }

        // Check that a GitHub token is available for contributor installation
        if ($mode === 'contributor') {
            $this->github = new \Github\Client();
            $token = (new Token())->read();

            $this->github->authenticate($token, null, \Github\Client::AUTH_ACCESS_TOKEN);
        }

        // Check that `git` is installed
        if ($mode === 'contributor') {
            try {
                $this->getGitPath();
            } catch (Exception $e) {
                throw new Exception('"git" command is required to install the contributor version of Winter CMS.');
            }
        }

        // Check that `composer` is installed
        if ($mode === 'contributor' || $mode === 'composer') {
            try {
                $this->getComposerPath();
            } catch (Exception $e) {
                throw new Exception('"composer" command is required to install the contributor version of Winter CMS.');
            }
        }

        // Check (or clear) the current path
        $path = $input->getArgument('path') ?? getcwd();
        if ($path === '.') {
            $path = getcwd();
        }

        if (!$input->getOption('force') || $path === getcwd()) {
            $this->comment('Checking path is empty...', OutputInterface::VERBOSITY_VERBOSE);
            $this->checkPathIsEmpty($path);
        } else {
            $this->comment('Clearing path...', OutputInterface::VERBOSITY_VERBOSE);
            $this->clearPath($input, $output, $path);
        }

        // Create path if required
        if (!file_exists($path)) {
            try {
                $this->comment('Creating empty directory in path...', OutputInterface::VERBOSITY_VERBOSE);
                mkdir($path, 0755, true);
            } catch (Throwable $e) {
                throw new Exception('Cannot create directory "' . $path . '" - ' . $e->getMessage());
            }
        }

        $this->getAppSettings($input, $output);
        $this->getDbSettings($input, $output);
        $this->getAdminSettings($input, $output);

        switch ($mode) {
            // case 'easy':
            //     $this->doEasyInstall($input, $output, $path);
            //     break;
            // case 'composer':
            //     $this->doComposerInstall($input, $output, $path);
            //     break;
            case 'contributor':
                $this->doContributorInstall($input, $output, $path);
                break;
        }
    }

    /**
     * Checks that a given path is empty.
     *
     * A path is considered empty if it doesn't exist, or is a directory with no files or subfolders within.
     *
     * @param string $path
     * @return void
     * @throws Exception if path is not empty
     * @throws Exception if path is a file
     */
    protected function checkPathIsEmpty(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_dir($path)) {
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $item) {
                if ($item->isDot()) {
                    continue;
                }
                // If a file or subfolder is found, break out at this point
                if ($item->isFile() || $item->isLink() || $item->isDir()) {
                    $message = 'Path "' . $path . '" is not empty.';
                    if ($path === getcwd()) {
                        $message .= ' Please clear out the current directory to install Winter CMS here.';
                    } else {
                        $message .= 'Use the --force option to ignore this.';
                    }
                    throw new Exception($message);
                }
            }
        }

        if (is_file($path)) {
            throw new Exception('Path "' . $path . '" is a file. You must install Winter CMS in a directory.');
        }
    }

    /**
     * Clears a path, and all its files and subfolders.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $path
     * @return void
     * @throws Exception If path is a file
     */
    protected function clearPath(InputInterface $input, OutputInterface $output, string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path)) {
            throw new Exception('Path "' . $path . '" is a file. You must install Winter CMS in a directory.');
        }

        // Remove the path completely
        if (PHP_OS_FAMILY == 'Windows') {
            $command = 'rd /s /q "' . $path . '"';
        } else {
            $command = 'rm -rf "' . $path . '"';
        }

        $this->runCommand($input, $output, $command);
    }

    protected function getAppSettings(InputInterface $input, OutputInterface $output)
    {
        $this->info('Determine application settings', OutputInterface::VERBOSITY_VERBOSE);

        // Application name
        $this->appName = $input->getOption('app-name') ?? $this->prompt('Application name?', $this->appName);

        // Application URL
        $this->appUrl = $input->getOption('app-url') ?? $this->prompt('Application URL', $this->appUrl);
    }

    protected function getDbSettings(InputInterface $input, OutputInterface $output)
    {
        $this->info('Determine database settings', OutputInterface::VERBOSITY_VERBOSE);

        $dbTypes = [
            'mysql',
            'pgsql',
            'sqlite',
            'sqlsrv'
        ];

        if (is_null($input->getOption('db-type')) || !in_array($input->getOption('db-type'), $dbTypes)) {
            $askHelper = $this->getHelper('question');

            $question = new ChoiceQuestion(
                PHP_EOL .
                '<comment>Please select the database type you wish to use.</comment>' . PHP_EOL . PHP_EOL .
                ' - <bold>mysql:</bold> (default) MySQL / MariaDB' . PHP_EOL .
                ' - <bold>pgsql:</bold> PostgreSQL.' . PHP_EOL .
                ' - <bold>sqlite:</bold> SQLite' . PHP_EOL .
                ' - <bold>sqlsrv:</bold> Microsoft SQL Server' . PHP_EOL,
                $dbTypes,
                'mysql'
            );
            $this->dbType = $askHelper->ask($input, $output, $question);
        }

        switch ($this->dbType) {
            case 'mysql':
                $this->getMysqlSettings($input, $output);
                break;
            case 'pgsql':
                $this->getPostgresSettings($input, $output);
                break;
            case 'sqlite':
                $this->getSqliteSettings($input, $output);
                break;
            case 'sqlsrv':
                $this->getSqlServerSettings($input, $output);
                break;
        }
    }

    protected function getMysqlSettings(InputInterface $input, OutputInterface $output)
    {
        // Database host
        $this->dbHost = $input->getOption('db-host') ?? $this->prompt('MySQL host address?', 'localhost');

        // Database port
        $this->dbPort = $input->getOption('db-port') ?? $this->promptInt('MySQL port', 3306);

        // Database name
        $this->dbName = $input->getOption('db-name') ?? $this->prompt('Database name?', $this->dbName);

        // Database username
        $this->dbUser = $input->getOption('db-user') ?? $this->prompt('MySQL username?', $this->dbUser);

        // Database password
        $this->dbPass = $input->getOption('db-pass') ?? $this->prompt('MySQL password?', $this->dbPass, true);
    }

    protected function getPostgresSettings(InputInterface $input, OutputInterface $output)
    {
        // Database host
        $this->dbHost = $input->getOption('db-host') ?? $this->prompt('PostgreSQL host address?', 'localhost');

        // Database port
        $this->dbPort = $input->getOption('db-port') ?? $this->promptInt('PostgreSQL port', 5432);

        // Database name
        $this->dbName = $input->getOption('db-name') ?? $this->prompt('Database name?', $this->dbName);

        // Database username
        $this->dbUser = $input->getOption('db-user') ?? $this->prompt('PostgreSQL username?', $this->dbUser);

        // Database password
        $this->dbPass = $input->getOption('db-pass') ?? $this->prompt('PostgreSQL password?', $this->dbPass, true);
    }

    protected function getSqliteSettings(InputInterface $input, OutputInterface $output)
    {
        // Database path
        $this->dbName = $input->getOption('db-name') ?? $this->prompt(
            'SQLite database path?',
            'storage/database.sqlite',
        );
    }

    protected function getSqlServerSettings(InputInterface $input, OutputInterface $output)
    {
        // Database host
        $this->dbHost = $input->getOption('db-host') ?? $this->prompt(
            'SQL Server host address?',
            '192.168.0.1\\SQLEXPRESS'
        );

        // Database port
        $this->dbPort = $input->getOption('db-port') ?? $this->promptInt('SQL Server port', 1433);

        // Database name
        $this->dbName = $input->getOption('db-name') ?? $this->prompt('Database name?', $this->dbName);

        // Database username
        $this->dbUser = $input->getOption('db-user') ?? $this->prompt('SQL Server username?', $this->dbUser);

        // Database password
        $this->dbPass = $input->getOption('db-pass') ?? $this->prompt('SQL Server password?', $this->dbPass, true);
    }

    protected function getAdminSettings(InputInterface $input, OutputInterface $output)
    {
        $this->info('Determine administrator account settings', OutputInterface::VERBOSITY_VERBOSE);

        // Admin username
        $this->adminUsername = $input->getOption('admin-user') ?? $this->prompt(
            'Admin username?',
            $this->adminUsername
        );

        // Admin password
        $this->adminPassword = $input->getOption('admin-pass') ?? $this->prompt(
            'Admin password?',
            $this->adminPassword,
            true
        );

        // Admin email
        $this->adminEmail = $input->getOption('admin-email') ?? $this->prompt(
            'Admin email address?',
            $this->adminEmail
        );
    }

    protected function doContributorInstall(InputInterface $input, OutputInterface $output, string $path)
    {
        $this->line();

        // Add a slight pause to indicate installation
        usleep(650000);

        $this->info('Installing Winter CMS...');

        // Set up fork
        $this->doCheck('Fork Winter CMS repository');

        // Check for the presence of a fork, and create a fork if not available
        $ownedRepos = $this->github->currentUser()->repositories();
        $found = false;
        $repository = null;

        foreach ($ownedRepos as $repo) {
            if ($repo['name'] === 'winter') {
                $found = true;
                $repository = $repo;
                break;
            }
        }

        if (!$found) {
            try {
                $repository = $this->github->api('repo')->forks()->create('wintercms', 'winter');
                $this->checkSuccessful('Winter CMS repository forked to ' . $repository['html_url'] . '.');
            } catch (Throwable $e) {
                $this->checkFailed('Unable to fork repository.', $e->getMessage());
            }
        } else {
            $this->checkSuccessful('Forked repository already available at ' . $repository['html_url'] . '.');
        }

        // Install
    }

    protected function runCommand(
        InputInterface $input = null,
        OutputInterface $output = null,
        string $command,
        bool $return = false
    ) {
        if (isset($input)) {
            if ($input->getOption('no-ansi')) {
                $command .= ' --no-ansi';
            }

            if ($input->getOption('quiet')) {
                $command .= ' --quiet';
            }
        }

        $process = Process::fromShellCommandline($command, null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: ' . $e->getMessage());
            }
        }

        if ($return) {
            $returned = [];
            $process->run(function ($type, $line) use ($returned) {
                $returned[] = $line;
            });

            return implode(PHP_EOL, $returned);
        } else {
            if (!isset($output)) {
                throw new Exception('You must provide an output interface.');
            }

            $process->run(function ($type, $line) use ($output) {
                $output->write('    ' . $line);
            });

            return $process;
        }
    }

    protected function getGitPath()
    {
        if (!empty($this->gitPath)) {
            return $this->gitPath;
        }

        if (PHP_OS_FAMILY == 'Windows') {
            $command = 'where.exe git.exe';
        } else {
            $command = 'which git 2>&1';
        }

        $return = $this->runCommand(null, null, $command, true);

        if (empty($return)) {
            throw new Exception('Unable to find git binary. Please ensure the `git` command is installed.');
        }

        if (strpos($return, PHP_EOL) !== false) {
            $lines = array_filter(explode(PHP_EOL, $return), function ($item) {
                return !empty($item);
            });
        } else {
            $lines = [$return];
        }

        return $this->gitPath = $lines[0];
    }

    protected function getComposerPath()
    {
        if (!empty($this->composerPath)) {
            return $this->composerPath;
        }

        if (PHP_OS_FAMILY == 'Windows') {
            $command = 'where.exe composer.exe';
        } else {
            $command = 'which composer 2>&1';
        }

        $return = $this->runCommand(null, null, $command, true);

        if (empty($return)) {
            // Try to find a local install of Composer
            if (
                file_exists(getcwd() . DIRECTORY_SEPARATOR . 'composer.phar')
                && is_executable(getcwd() . DIRECTORY_SEPARATOR . 'composer.phar')
            ) {
                return $this->composerPath = getcwd() . DIRECTORY_SEPARATOR . 'composer.phar';
            }
            if (
                file_exists(getcwd() . DIRECTORY_SEPARATOR . 'composer')
                && is_executable(getcwd() . DIRECTORY_SEPARATOR . 'composer')
            ) {
                return $this->composerPath = getcwd() . DIRECTORY_SEPARATOR . 'composer';
            }

            throw new Exception('Unable to find Composer binary. Please ensure the `composer` command is installed.');
        }

        if (strpos($return, PHP_EOL) !== false) {
            $lines = array_filter(explode(PHP_EOL, $return), function ($item) {
                return !empty($item);
            });
        } else {
            $lines = [$return];
        }

        return $this->gitPath = $lines[0];
    }
}
