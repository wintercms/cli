<?php namespace Winter\Cli;

use DirectoryIterator;
use Symfony\Component\Console\Application as SymfonyApplication;

/**
 * CLI Application.
 *
 * @since 0.1.0
 * @author Ben Thomson
 */
class Application extends SymfonyApplication
{
    protected static $name = "\033[1;34mWinter\033[0m CLI";

    protected static $version = '@version@ (@datetime@)';

    /**
     * Winter CLI constructor.
     *
     * @param string $name
     * @param string $version
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->setName(static::$name);
        $this->setVersion(static::$version);

        $this->detectCommands();
    }

    /**
     * Detect and enable commands that are available.
     *
     * @param void
     */
    protected function detectCommands()
    {
        if (!is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'Commands')) {
            return;
        }

        $iterator = new DirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'Commands');

        foreach ($iterator as $dir) {
            if ($dir->isDot()) {
                continue;
            }
            if (!$dir->isDir()) {
                continue;
            }

            if (file_exists($dir->getPathname() . DIRECTORY_SEPARATOR . 'Command.php')) {
                $class = 'Winter\\Cli\\Commands\\' . $dir->getFilename() . '\\Command';
                $this->add(new $class);
            }
        }
    }
}
