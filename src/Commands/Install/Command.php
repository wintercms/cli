<?php namespace BennoThommo\OctoberCli\Commands\Install;

use DirectoryIterator;
use Exception;
use RuntimeException;
use BennoThommo\OctoberCli\BaseCommand;
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
    /** @inheritDoc */
    protected static $defaultName = 'install';

    /** @var string Application name */
    protected $appName = 'October CMS';

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
    protected $dbName = 'october';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Installs October CMS.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'This command allows you to install October CMS into a specified path.' . PHP_EOL . PHP_EOL .
                'Three types of installation are available:' . PHP_EOL .
                ' - Easy install: Installs October CMS using the October CMS marketplace.' . PHP_EOL .
                ' - Composer install: Installs October CMS using Composer.' . PHP_EOL .
                ' - Contributor install: Installs October CMS using Composer, and sets up the installation in order' .
                ' to allow the user to contribute to October CMS.'
            )

            // arguments
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The path to the October CMS project.'
            )

            // options
            ->addOption(
                'composer',
                'c',
                InputOption::VALUE_NONE,
                'Installs October CMS using Composer.'
            )
            ->addOption(
                'contributor',
                null,
                InputOption::VALUE_NONE,
                'Installs October CMS using Composer, and sets up the installation in order' .
                ' to allow the user to contribute to October CMS.'
            )
            ->addOption(
                'easy',
                'e',
                InputOption::VALUE_NONE,
                'Installs October CMS using the October CMS marketplace.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forces the installation of October CMS (will delete any files within the path specified).'
            )
            // settings options
            ->addOption(
                'app-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the name of the October CMS project.'
            )
            ->addOption(
                'app-url',
                null,
                InputOption::VALUE_REQUIRED,
                'Specifies the URL of the October CMS project.'
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
                ' - <bold>Easy install (easy):</bold> (default) Installs October CMS using the October CMS' .
                ' marketplace.' . PHP_EOL .
                ' - <bold>Composer install (composer):</bold> Installs October CMS using Composer.' . PHP_EOL .
                ' - <bold>Contributor install (contributor):</bold> Installs October CMS using Composer, and sets up' .
                ' the installation in order to allow the user to contribute to October CMS.' . PHP_EOL,
                ['easy', 'composer', 'contributor'],
                'easy'
            );
            $mode = $askHelper->ask($input, $output, $question);
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

        // switch ($mode) {
        //     case 'easy':
        //         $this->doEasyInstall($input, $output, $path);
        //         break;
        //     case 'composer':
        //         $this->doComposerInstall($input, $output, $path);
        //         break;
        //     case 'contributor':
        //         $this->doContributorInstall($input, $output, $path);
        //         break;
        // }

        print_r([
            'appName' => $this->appName,
            'appUrl' => $this->appUrl,
            'dbType' => $this->dbType,
            'dbHost' => $this->dbHost,
            'dbPort' => $this->dbPort,
            'dbName' => $this->dbName,
            'dbUser' => $this->dbUser,
            'dbPass' => $this->dbPass,
        ]);
        die();
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
                        $message .= ' Please clear out the current directory to install October CMS here.';
                    } else {
                        $message .= 'Use the --force option to ignore this.';
                    }
                    throw new Exception($message);
                }
            }
        }

        if (is_file($path)) {
            throw new Exception('Path "' . $path . '" is a file. You must install October CMS in a directory.');
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
            throw new Exception('Path "' . $path . '" is a file. You must install October CMS in a directory.');
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
                ' - <bold>mysql:</bold> (default) MySQL / MariaDB' .
                ' marketplace.' . PHP_EOL .
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

    protected function runCommand(InputInterface $input, OutputInterface $output, string $command): Process
    {
        if ($input->getOption('no-ansi')) {
            $command .= ' --no-ansi';
        }

        if ($input->getOption('quiet')) {
            $command .= ' --quiet';
        }

        $process = Process::fromShellCommandline($command, null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: ' . $e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write('    ' . $line);
        });

        return $process;
    }
}
