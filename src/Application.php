<?php
namespace BennoThommo\OctoberCli;

use DirectoryIterator;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    protected static $name = 'October CLI';

    protected static $version = '0.1.0';

    protected static $logo = <<<LOGO
====================================================================

 .d8888b.   .o8888b.   db  .d8888b.  d8888b. d88888b d8888b.  .d888b.
.8P    Y8. d8P    Y8   88 .8P    Y8. 88  `8D 88'     88  `8D .8P , Y8.
88      88 8P      oooo88 88      88 88oooY' 88oooo  88oobY' 88  |  88
88      88 8b      ~~~~88 88      88 88~~~b. 88~~~~  88`8b   88  |/ 88
`8b    d8' Y8b    d8   88 `8b    d8' 88   8D 88.     88 `88. `8b | d8'
 `Y8888P'   `Y8888P'   YP  `Y8888P'  Y8888P' Y88888P 88   YD  `Y888P'

====================================================================\n\n
LOGO;
    /**
     * October CLI constructor.
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
                $class = 'BennoThommo\\OctoberCli\\Commands\\' . $dir->getFilename() . '\\Command';
                $this->add(new $class);
            }
        }
    }
}
