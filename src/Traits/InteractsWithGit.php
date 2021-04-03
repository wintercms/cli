<?php namespace Winter\Cli\Traits;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Winter\Cli\Filesystem\DataDir;

trait InteractsWithGit
{
    /**
     * @var string The path to the working Git repository.
     */
    protected $gitRepoPath;

    /**
     * @var string The path to the "git" binary.
     */
    protected $gitPath;

    /**
     * @var string The name of the repository. Used if a Git repository is automatically created.
     *
     * protected $repoName = 'repo';
     */

    /**
     * Runs a Git command.
     *
     * @param array $command
     * @param boolean $includeGitRepoPath If true, the Git repo path will be prepended to the command.
     * @return Process
     */
    protected function runGitCommand($command, $includeGitRepoPath = true)
    {
        if (empty($this->getGitPath())) {
            return;
        }

        if ($includeGitRepoPath) {
            array_unshift($command, '--git-dir=' . $this->getGitRepoPath() . '');
        }
        array_unshift($command, $this->getGitPath());

        $process = new Process($command);
        $this->line('Running Git command: ' . implode(' ', $command), OutputInterface::VERBOSITY_DEBUG);
        $process->run();

        return $process;
    }

    /**
     * Determines the path to the "git" binary.
     *
     * @return string
     */
    protected function getGitPath()
    {
        if (!empty($this->gitPath)) {
            return $this->gitPath;
        }

        if (PHP_OS_FAMILY == 'Windows') {
            $command = ['where.exe', 'git.exe'];
        } else {
            $command = ['which', 'git'];
        }

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error(
                'Unable to determine the correct path for the "git" binary. Please make sure it is installed.'
            );
            return;
        }

        $path = $process->getOutput();

        if (empty($path)) {
            $this->error(
                'Unable to determine the correct path for the "git" binary. Please make sure it is installed.'
            );
            return;
        }

        return $this->gitPath = trim($path);
    }

    /**
     * Returns the Git repository path.
     *
     * If a Git repository path has not been previously defined, it will be created within the data directory.
     *
     * @return string
     */
    protected function getGitRepoPath()
    {
        if (isset($this->gitRepoPath)) {
            return $this->gitRepoPath;
        }

        return (new DataDir)->mkdir($this->repoName ?? 'repo');
    }

    /**
     * Sets the path to the Git repository.
     *
     * @param string $gitRepoPath
     * @return void
     */
    protected function setGitRepoPath(string $gitRepoPath)
    {
        if (!is_dir($gitRepoPath)) {
            throw new Exception('Path to Git repository "' . $gitRepoPath . '" not found.');
        }

        $this->gitRepoPath = rtrim($gitRepoPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Determines if the repository exists.
     *
     * @return bool
     */
    protected function repositoryExists()
    {
        return is_dir($this->getGitRepoPath())
            && file_exists($this->getGitRepoPath() . '/HEAD')
            && is_dir($this->getGitRepoPath() . '/refs');
    }
}
