<?php namespace BennoThommo\OctoberCli\Commands\Version;

use DirectoryIterator;
use Exception;

/**
 * Stores the file manifest for this October CMS installation.
 *
 * This manifest is a file checksum of all files within this October CMS installation. When compared to the source
 * manifest, this allows us to determine the current installation's build number.
 *
 * Based off the following implementation, but decoupled for this CLI helpers' use.
 * https://github.com/octobercms/october/blob/develop/modules/system/classes/FileManifest.php
 *
 * @since 0.1.0
 * @author Ben Thomson
 */
class FileManifest
{
    /**
     * @var string Root folder of this installation.
     */
    protected $root;

    /**
     * @var array Modules to store in manifest.
     */
    protected $modules = ['system', 'backend', 'cms'];

    /**
     * @var array Files cache.
     */
    protected $files = [];

    /**
     * Constructor.
     *
     * @param string $root The root folder to get the file list from. If not provided, defaults to the current working
     *  directory.
     * @param array $modules An array of modules to include in the file manifest.
     */
    public function __construct($root = null, array $modules = null)
    {
        if (isset($root)) {
            $this->setRoot($root);
        } else {
            $this->setRoot(getcwd());
        }

        $this->validateRoot();

        if (isset($modules)) {
            $this->setModules($modules);
        } else {
            $this->detectModules();
        }
    }

    /**
     * Sets the root folder.
     *
     * @param string $root
     * @throws Exception If the specified root does not exist.
     */
    public function setRoot($root)
    {
        if (is_string($root)) {
            $this->root = realpath($root);

            if ($this->root === false || !is_dir($this->root)) {
                throw new Exception(
                    'Invalid root specified for the file manifest.'
                );
            }
        }

        return $this;
    }

    /**
     * Validates that the root folder provided contains an October CMS installation.
     *
     * This looks for the following:
     *  - a `modules` directory with, at the very least, a `system` subdirectory
     *  - a `themes` directory
     *  - a `config/app.php` file
     *  - a `config/cms.php` file
     *
     * If all four of the above paths are present, it's reasonable to assume we're working with an October CMS install.
     *
     * @throws Exception If the specified root does not contain an October CMS installation.
     * @return void
     */
    protected function validateRoot(): void
    {
        $paths = [
            $this->root . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'system',
            $this->root . DIRECTORY_SEPARATOR . 'themes',
            $this->root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php',
            $this->root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'cms.php',
        ];

        foreach ($paths as $path) {
            $realPath = realpath($path);

            if (!$realPath) {
                throw new Exception('The given path does not appear to be an October CMS installation (missing ' . $path . ')');
            }
        }
    }

    /**
     * Sets the modules.
     *
     * @param array $modules
     */
    public function setModules(array $modules)
    {
        $this->modules = array_map(function ($module) {
            return strtolower($module);
        }, $modules);

        return $this;
    }

    /**
     * Detects the modules installed in the root folder and sets them in this manifest.
     *
     * @return void
     */
    public function detectModules(): void
    {
        $iterator = new DirectoryIterator($this->root . DIRECTORY_SEPARATOR . 'modules');
        $validModules = ['system', 'cms', 'backend'];
        $foundModules = [];

        foreach ($iterator as $dir) {
            if ($dir->isDot()) {
                continue;
            }
            if (!$dir->isDir()) {
                continue;
            }
            if (in_array($dir->getFilename(), $validModules)) {
                $foundModules[] = $dir->getFilename();
            }
        }

        $this->setModules($foundModules);
    }

    /**
     * Detects modules
     */

    /**
     * Gets a list of files and their corresponding hashsums.
     *
     * @return array
     */
    public function getFiles()
    {
        if (count($this->files)) {
            return $this->files;
        }

        $files = [];

        foreach ($this->modules as $module) {
            $path = $this->root . '/modules/' . $module;

            if (!is_dir($path)) {
                continue;
            }

            foreach ($this->findFiles($path) as $file) {
                $files[$this->getFilename($file)] = hash('sha3-256', $this->normalizeFileContents($file));
            }
        }

        return $this->files = $files;
    }

    /**
     * Gets the checksum of a specific install.
     *
     * @return array
     */
    public function getModuleChecksums()
    {
        if (!count($this->files)) {
            $this->getFiles();
        }

        $modules = [];
        foreach ($this->modules as $module) {
            $modules[$module] = '';
        }

        foreach ($this->files as $path => $hash) {
            // Determine module
            $module = explode('/', $path)[2];

            $modules[$module] .= $hash;
        }

        return array_map(function ($moduleSum) {
            return hash('sha3-256', $moduleSum);
        }, $modules);
    }

    /**
     * Finds all files within the path.
     *
     * @param string $basePath The base path to look for files within.
     * @return array
     */
    protected function findFiles(string $basePath)
    {
        $files = [];

        $iterator = function ($path) use (&$iterator, &$files) {
            foreach (new \DirectoryIterator($path) as $item) {
                if ($item->isDot() === true) {
                    continue;
                }
                if ($item->isFile()) {
                    $files[] = $item->getPathName();
                }
                if ($item->isDir()) {
                    $iterator($item->getPathname());
                }
            }
        };
        $iterator($basePath);

        // Ensure files are sorted so they are in a consistent order, no matter the way the OS returns the file list.
        sort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * Returns the filename without the root.
     *
     * @param string $file
     * @return string
     */
    protected function getFilename(string $file): string
    {
        return str_replace($this->root, '', $file);
    }

    /**
     * Normalises the file contents, irrespective of OS.
     *
     * @param string $file
     * @return string
     */
    protected function normalizeFileContents(string $file): string
    {
        if (!is_file($file)) {
            return '';
        }

        $contents = file_get_contents($file);

        return str_replace(PHP_EOL, "\n", $contents);
    }
}
