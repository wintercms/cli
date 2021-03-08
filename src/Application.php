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
    protected static $name = "Winter \033[1;34mCLI\033[0m";

    protected static $version = '@version@ (@datetime@)';

    protected static $logo = <<<LOGO

db   d8b   db d888888b d8b   db d888888b d88888b d8888b.       \033[1;34m...\033[0m
88   I8I   88   `88'   888o  88 `~~88~~' 88'     88  `8D  \033[1;34m... ..... ...\033[0m
88   I8I   88    88    88V8o 88    88    88ooooo 88oobY'    \033[1;34m.. ... ..\033[0m
Y8   I8I   88    88    88 V8o88    88    88~~~~~ 88`8b      \033[1;34m.. ... ..\033[0m
`8b d8'8b d8'   .88.   88  V888    88    88.     88 `88.  \033[1;34m... ..... ...\033[0m
 `8b8' `8d8'  Y888888P VP   V8P    YP    Y88888P 88   YD       \033[1;34m...\033[0m
\n
LOGO;

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
     * Gets the help message.
     *
     * @return string A help message
     */
    public function getHelp()
    {
        return static::$logo . parent::getHelp();
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
