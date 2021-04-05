<?php namespace Winter\Cli\Traits;

use Exception;
use Symfony\Component\Process\Process;
use Throwable;

trait InteractsWithFiles
{
    /**
     * Clears a path, and all its files and subfolders.
     *
     * @param string $path
     * @return void
     * @throws ApplicationException If path is a file
     */
    protected function clearPath($path)
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_file($path)) {
            try {
                unlink($path);
            } catch (Throwable $e) {
                throw new Exception('Unable to remove file at "' . $path . '", please check permissions.');
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $command = ['rd', '/s', '/q', $path];
        } else {
            $command = ['rm', '-rf', $path];
        }

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception('Unable to clear path "' . $path . '"');
        }
    }
}
