<?php namespace Winter\Cli\Filesystem;

use Exception;
use Throwable;

/**
 * Data directory handler.
 *
 * Allows for selection and processing of files within a specific data directory. The data directory should be kept
 * in an OS-specified configuration directory.
 *
 * @since 0.2.2
 * @author Ben Thomson
 */
class DataDir
{
    /** @var array Paths to attempt to store in, in order of priority. */
    protected $paths = [];

    /** @var string Folder name */
    protected $folderName = 'winter-cli';

    /** @var string Fallback path, if the paths above do not exist */
    protected $fallbackPath;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->paths = [
            $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.config',
            $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share',
            $_SERVER['HOME'] . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local'
        ];

        $this->fallbackPath = $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.winter-cli';
    }

    /**
     * Gets a file into the data directory.
     *
     * If the file does not exist, returns `false`.
     *
     * @param string $path
     * @return string|bool
     */
    public function get(string $path)
    {
        $path = $this->resolvePath($path);

        if (!$path || !is_readable($path)) {
            throw new Exception('Unable to get file "' . $path . '", please check permissions.');
        }

        return file_get_contents($path);
    }

    /**
     * Gets a path to a file or directory in the data directory.
     *
     * If the file does not exist, returns `false`.
     *
     * @param string $path
     * @return string|bool
     */
    public function path(string $path)
    {
        if (!$this->exists($path)) {
            return false;
        }

        return $this->resolvePath($path);
    }

    /**
     * Finds if a file exists within the data directory.
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path)
    {
        $path = $this->resolvePath($path);

        if (!$path || !is_readable($path)) {
            return false;
        }

        return true;
    }

    /**
     * Puts a file into the data directory.
     *
     * If successful, will return the path written to.
     *
     * @param string $path
     * @param string $content
     * @return string
     */
    public function put(string $path, string $content)
    {
        $path = $this->resolvePath($path);

        try {
            $dir = dirname($path);

            if (is_dir($dir) && !is_writeable($dir)) {
                throw new Exception('Path not writable');
            }

            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                throw new Exception('Directory not writable');
            }

            if (is_file($path) && !is_writeable($path)) {
                throw new Exception('Path not writable');
            }

            file_put_contents($path, $content, LOCK_EX);
        } catch (Throwable $e) {
            throw new Exception('Unable to put file "' . $path . '", please check permissions.');
        }

        return $path;
    }

    /**
     * Creates a directory within the data directory.
     *
     * If successful, will return the path written to.
     *
     * @param string $path
     * @param string $content
     * @return string
     * @throws Exception If the directory cannot be written.
     */
    public function mkdir(string $path)
    {
        $path = $this->resolvePath($path);

        if (is_dir($path)) {
            return $path;
        }

        if (is_file($path)) {
            throw new Exception('A file already exists as path "' . $path . '"');
        }

        try {
            $dir = dirname($path);

            if (is_dir($dir) && !is_writeable($dir)) {
                throw new Exception('Path not writable');
            }

            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                throw new Exception('Directory not writable');
            }

            mkdir($path, 0755);
        } catch (Throwable $e) {
            throw new Exception('Unable to make directory "' . $path . '", please check permissions.');
        }

        return $path;
    }

    /**
     * Changes file permissions on a path in the data directory
     *
     * If the file does not exist, returns `false`.
     *
     * @param string $path
     * @param octal $chmod
     * @return bool
     * @throws Exception If the permissions cannot be written.
     */
    public function chmod(string $path, $chmod)
    {
        if (!$this->exists($path)) {
            return false;
        }

        $path = $this->resolvePath($path);

        try {
            chmod($path, $chmod);
        } catch (Throwable $e) {
            throw new Exception('Unable to chmod "' . $path . '", please check permissions.');
        }
    }

    /**
     * Find the first available data directory.
     *
     * If none exist, try to create the fallback directory and return that. If it can't be created, an exception will
     * be thrown.
     *
     * @return string
     * @throws Exception If a data directory is unavailable, and the fallback path cannot be created.
     */
    protected function getDirectory(): string
    {
        foreach ($this->paths as $dir) {
            if (!is_dir($dir) || !is_writable($dir)) {
                continue;
            }

            return $dir . DIRECTORY_SEPARATOR . $this->folderName;
        }

        if (!is_dir($this->fallbackPath)) {
            if (!mkdir($this->fallbackPath, 0755, true)) {
                throw new Exception('Unable to provide a data directory for use.');
            }
        }

        return $this->fallbackPath;
    }

    /**
     * Resolves a path to the data directory.
     *
     * Returns the resolved path if available, otherwise, returns `false`.
     *
     * @param string $path
     * @return string|bool
     */
    protected function resolvePath(string $path)
    {
        $root = $this->getDirectory();
        $fullPath = PathResolver::resolve($root . DIRECTORY_SEPARATOR . $path);

        if (!$fullPath) {
            return false;
        }

        if (!PathResolver::within($fullPath, $root)) {
            return false;
        }

        return $fullPath;
    }
}
