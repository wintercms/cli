<?php namespace Winter\Cli\Commands\Split;

use Phar;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Winter\Cli\BaseCommand;
use Winter\Cli\GitHub\Token;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Winter\Cli\Filesystem\DataDir;
use Winter\Cli\Traits\InteractsWithFiles;
use Winter\Cli\Traits\InteractsWithGit;

/**
 * Split command
 *
 * @since 0.3.0
 * @author Ben Thomson
 */
class Command extends BaseCommand
{
    use InteractsWithFiles;
    use InteractsWithGit;

    /**
     * @inheritDoc
     */
    protected static $defaultName = 'split';

    /**
     * @var string GitHub token
     */
    protected $token;

    /**
     * @var string Origin repository. Requires one string placeholder in the URL to insert a token.
     */
    protected $origin = 'https://%s@github.com/wintercms/winter.git';

    /**
     * @inheritDoc
     */
    protected $repoName = 'split-repo';

    /**
     * @var array Remote subsplit repositories. Each remote requires one string placeholder in the URL to insert
     * a token.
     */
    protected $remotes = [
        'system' => [
            'prefix' => 'modules/system',
            'url' => 'https://%s@github.com/wintercms/wn-system-module.git',
        ],
        'backend' => [
            'prefix' => 'modules/backend',
            'url' => 'https://%s@github.com/wintercms/wn-backend-module.git',
        ],
        'cms' => [
            'prefix' => 'modules/cms',
            'url' => 'https://%s@github.com/wintercms/wn-cms-module.git',
        ],
    ];

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Runs a subsplit to publish the Winter CMS modules in their own repositories.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'This is used by the maintainers to push changes from the main repository to the module subsplit'
                . ' repositories.'
            )

            // hide this command from normal usage
            ->setHidden(true)

            // options
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_REQUIRED,
                'Publishes a branch in the subsplit repositories.'
            )
            ->addOption(
                'git',
                'g',
                InputOption::VALUE_REQUIRED,
                'The path to the "git" binary. If this is not provided, it will be found automatically.'
            )
            ->addOption(
                'remove-branch',
                null,
                InputOption::VALUE_REQUIRED,
                'Removes a branch in the subsplit repositories.'
            )
            ->addOption(
                'remove-tag',
                null,
                InputOption::VALUE_REQUIRED,
                'Removes a tag in the subsplit repositories.'
            )
            ->addOption(
                'sync',
                's',
                InputOption::VALUE_NONE,
                'Fully synchronises all branches with the subsplit repositories.'
            )
            ->addOption(
                'tag',
                'a',
                InputOption::VALUE_REQUIRED,
                'Publishes a tag in the subsplit repositories.'
            )
            ->addOption(
                'work-repo',
                'w',
                InputOption::VALUE_REQUIRED,
                'Defines a custom location for the working repository.'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->error('Windows is not supported for subsplitting.');
            return;
        }

        $this->token = (new Token())->read();

        if ($input->getOption('work-repo')) {
            $this->setGitRepoPath($input->getOption('work-repo'));
        }

        // Determine action being used
        $action = null;

        foreach (['branch', 'remove-branch', 'remove-tag', 'tag', 'sync'] as $option) {
            if ($input->getOption($option)) {
                $action = $option;
                break;
            }
        }

        if (is_null($action)) {
            $this->error(
                'You must specify an action to take: one of --branch, --remove-branch, --remove-tag, --tag or --sync'
            );
            return;
        }

        // Set up the work repository
        $this->comment('Setting up work repository...');

        if (!$this->repositoryExists()) {
            $this->line(' - Creating work repository.');
            $this->createWorkRepo();
        } else {
            $this->line(' - Work repository already exists.');
            $this->line(' - Updating work repository.');
            $this->updateWorkRepo();
        }

        // Execute action
        switch ($action) {
            case 'sync':
            default:
                $this->doSync();
                break;
            case 'branch':
                $this->doBranchSync();
                break;
            case 'remove-branch':
                $this->doRemoveBranch();
                break;
            case 'tag':
                $this->doTagSync();
                break;
            case 'remove-tag':
                $this->doRemoveTag();
                break;
        }
    }

    /**
     * Executes a full synchronisation (all branches) of the subsplit repositories.
     *
     * This function will remove any branches on subsplits that no longer exist on origin.
     *
     * @return void
     */
    protected function doSync()
    {
        $this->comment('Performing full branch sync of subsplits...');

        $this->line(' - Finding branches.');

        $this->line(' - Synchronising branches.');

        $branches = $this->getBranches();

        // Create progress bar
        $progress = new ProgressBar($this->output);

        foreach ($progress->iterate($branches) as $branch) {
            $progress->clear();
            $this->line('   - Syncing "' . $branch . '" branch.');
            $progress->display();

            $this->syncBranch($branch);
        }

        $progress->clear();

        $this->line(' - Cleaning orphaned branches.');

        // Create progress bar
        $progress = new ProgressBar($this->output);

        foreach ($progress->iterate(array_keys($this->remotes)) as $remote) {
            $remoteBranches = $this->getBranches($remote);
            $removedBranches = array_diff($remoteBranches, $branches);

            if (count($removedBranches)) {
                foreach ($removedBranches as $removedBranch) {
                    $progress->clear();
                    $this->line('   - Removing branch "' . $branch . '" from remote "' . $remote . '".');
                    $progress->display();

                    $this->deleteBranch($remote, $removedBranch);
                }
            }
        }

        $progress->clear();
    }

    /**
     * Executes a synchronisation of a branch to the subsplit repositories.
     *
     * @return void
     */
    protected function doBranchSync()
    {
        $branch = $this->input->getOption('branch');

        $this->comment('Performing sync of "' . $branch . '" to subsplits...');

        $this->line(' - Syncing "' . $branch . '" branch.');

        $this->syncBranch($branch);
    }

    /**
     * Executes a synchronisation of a tag to the subsplit repositories.
     *
     * @return void
     */
    protected function doTagSync()
    {
        $tag = $this->input->getOption('tag');

        $this->comment('Performing sync of "' . $tag . '" to subsplits...');

        $this->line(' - Syncing "' . $tag . '" tag.');

        $this->syncTag($tag);
    }

    /**
     * Executes a deletion of a branch from the subsplit repositories.
     *
     * @return void
     */
    protected function doRemoveBranch()
    {
        $branch = $this->input->getOption('remove-branch');

        $this->comment('Deleting branch "' . $branch . '" from subsplits...');

        // Create progress bar
        $progress = new ProgressBar($this->output);

        foreach ($progress->iterate(array_keys($this->remotes)) as $remote) {
            $progress->clear();
            $this->line(' - Removing branch "' . $branch . '" from "' . $remote . '".');
            $progress->display();

            if ($this->branchExists($remote, $branch)) {
                $this->deleteBranch($remote, $branch);
                $progress->clear();
                $this->line(' - Removed from "' . $remote . '".');
                $progress->display();
            } else {
                $progress->clear();
                $this->line(' - Branch doesn\'t exist on "' . $remote . '". Skipping.');
                $progress->display();
            }
        }

        $progress->clear();
    }

    /**
     * Executes a deletion of a tag from the subsplit repositories.
     *
     * @return void
     */
    protected function doRemoveTag()
    {
        $tag = $this->input->getOption('remove-tag');

        $this->comment('Deleting tag "' . $tag . '" from subsplits...');

        // Create progress bar
        $progress = new ProgressBar($this->output);

        foreach ($progress->iterate(array_keys($this->remotes)) as $remote) {
            $progress->clear();
            $this->line(' - Removing tag "' . $tag . '" from "' . $remote . '".');
            $progress->display();

            if ($this->tagExists($remote, $tag)) {
                $this->deleteTag($remote, $tag);
                $progress->clear();
                $this->line(' - Removed from "' . $remote . '".');
                $progress->display();
            } else {
                $progress->clear();
                $this->line(' - Tag doesn\'t exist on "' . $remote . '". Skipping.');
                $progress->display();
            }
        }

        $progress->clear();
    }

    /**
     * Get branches from a repository.
     *
     * By default, this will get the origin branches, but you may specify an optional remote to get branches from the
     * remote.
     *
     * @return array
     */
    protected function getBranches($remote = null)
    {
        if (is_null($remote)) {
            $command = [
                'branch',
                '-l'
            ];
        } elseif (in_array($remote, array_keys($this->remotes))) {
            $command = [
                'branch',
                '-la'
            ];
        } else {
            throw new Exception('Invalid remote "' . $remote . '" specified.');
        }

        $process = $this->runGitCommand($command);

        if (!$process->isSuccessful()) {
            $this->error('Unable to determine available branches.');
            return;
        }

        $branches = array_map(
            function ($item) use ($remote) {
                $branch = trim(str_replace('* ', '', $item));

                if (!is_null($remote)) {
                    $branch = str_ireplace('remotes/' . $remote . '/', '', $branch);
                }

                return $branch;
            },
            array_filter(
                preg_split('/[\n\r]+/', trim($process->getOutput()), -1, PREG_SPLIT_NO_EMPTY),
                function ($item) use ($remote) {
                    if (is_null($remote)) {
                        return true;
                    }

                    return preg_match('/^ +remotes\\/' . preg_quote($remote, '/') . '/i', $item);
                }
            )
        );

        return $branches;
    }

    /**
     * Determines if a branch exists on a given remote.
     *
     * @param string $remote
     * @param string $branch
     * @return bool
     */
    protected function branchExists($remote, $branch)
    {
        if (!in_array($remote, array_keys($this->remotes))) {
            throw new Exception('Invalid remote "' . $remote . '" specified.');
        }

        $branches = $this->getBranches($remote);

        return in_array($branch, $branches);
    }

    /**
     * Determines if a tag exists on a given remote.
     *
     * @param string $remote
     * @param string $tag
     * @return bool
     */
    protected function tagExists($remote, $tag)
    {
        if (!in_array($remote, array_keys($this->remotes))) {
            throw new Exception('Invalid remote "' . $remote . '" specified.');
        }

        $process = $this->runGitCommand([
            'ls-remote',
            $remote,
            'refs/tags/' . $tag
        ]);

        if (!$process->isSuccessful()) {
            $this->error('Unable to determine available tags.');
            return;
        }

        $output = trim($process->getOutput());

        return !empty($output);
    }

    /**
     * Deletes a branch on a remote.
     *
     * @param string $remote
     * @param string $branch
     * @return void
     */
    protected function deleteBranch($remote, $branch)
    {
        if (!in_array($remote, array_keys($this->remotes))) {
            throw new Exception('Invalid remote "' . $remote . '" specified.');
        }

        $process = $this->runGitCommand([
            'push',
            '--delete',
            $remote,
            $branch
        ]);

        if (!$process->isSuccessful()) {
            $this->error('Unable to delete branch "' . $branch . '" on remote "' . $remote . '"');
            return;
        }
    }

    /**
     * Deletes a tag on a remote.
     *
     * @param string $remote
     * @param string $branch
     * @return void
     */
    protected function deleteTag($remote, $tag)
    {
        if (!in_array($remote, array_keys($this->remotes))) {
            throw new Exception('Invalid remote "' . $remote . '" specified.');
        }

        $process = $this->runGitCommand([
            'push',
            '--delete',
            $remote,
            $tag
        ]);

        if (!$process->isSuccessful()) {
            $this->error('Unable to delete tag "' . $tag . '" on remote "' . $remote . '"');
            return;
        }
    }

    /**
     * Synchronises a branch with all subsplits.
     *
     * @param string $branch
     * @return void
     */
    protected function syncBranch($branch)
    {
        foreach ($this->remotes as $remote => $split) {
            // Process subsplit through "splitsh" utility for given remote and module
            $process = new Process([
                $this->getSplitshPath(),
                '--origin=heads/' . $branch,
                '--target=heads/' . $remote . '-' . $branch,
                '--prefix=' . $split['prefix'],
                '--path=' . $this->getGitRepoPath()
            ]);
            $this->line('Running command: ' . $process->getCommandLine(), OutputInterface::VERBOSITY_DEBUG);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new Exception(
                    'Unable to create a subsplit of the ' . $remote . ' module from "' . $split['prefix'] . '". '
                    . $process->getErrorOutput()
                );
            }

            // Push to the remote
            $process = $this->runGitCommand([
                'push',
                '-f',
                $remote,
                'heads/' . $remote . '-' . $branch . ':refs/heads/' . $branch
            ]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new Exception(
                    'Unable to push a subsplit of the ' . $remote . ' module from "' . $split['prefix'] . '". '
                    . $process->getErrorOutput()
                );
            }
        }
    }

    /**
     * Synchronises a tag with all subsplits.
     *
     * @param string $branch
     * @return void
     */
    protected function syncTag($tag)
    {
        foreach ($this->remotes as $remote => $split) {
            // Process subsplit through "splitsh" utility for given remote and module
            $process = new Process([
                $this->getSplitshPath(),
                '--origin=tags/' . $tag,
                '--target=tags/' . $remote . '-' . $tag,
                '--prefix=' . $split['prefix'],
                '--path=' . $this->getGitRepoPath()
            ]);
            $this->line('Running command: ' . $process->getCommandLine(), OutputInterface::VERBOSITY_DEBUG);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new Exception(
                    'Unable to create a subsplit of the tag"' . $tag . '" to the "' . $remote . '" module from "'
                    . $split['prefix'] . '". '
                    . $process->getErrorOutput()
                );
            }

            // Push to the remote
            $process = $this->runGitCommand([
                'push',
                '-f',
                $remote,
                'tags/' . $remote . '-' . $tag . ':refs/tags/' . $tag
            ]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new Exception(
                    'Unable to push tag"' . $tag . '" to the "' . $remote . '" module from "' . $split['prefix'] . '". '
                    . $process->getErrorOutput()
                );
            }
        }
    }

    /**
     * Creates a working bare Git repository for subsplitting the modules and determining commits.
     *
     * By default, this will be stored in the "storage/temp/split-repo" directory relative to the base path, but it can
     * be modified by the "--work-repo" option in the command line.
     *
     * @return void
     */
    protected function createWorkRepo()
    {
        $this->clearPath($this->getGitRepoPath());

        if (!is_dir($this->getGitRepoPath()) && !mkdir($this->getGitRepoPath(), 0755, true)) {
            throw new Exception(
                'Unable to create a work repository in path "' . $this->getGitRepoPath() . '". Please check your'
                . ' permissions.'
            );
        }

        $command = [
            'clone',
            '--bare',
            sprintf($this->origin, $this->token),
            $this->getGitRepoPath()
        ];

        $process = $this->runGitCommand($command, false);
        if (!$process->isSuccessful()) {
            $this->error(
                'Unable to create work repository in path "' . $this->getGitRepoPath() . '". '
                . $process->getErrorOutput()
            );
        } else {
            $this->line(' - Created and checked out bare repository.');
        }

        $this->updateWorkRepo();
    }

    /**
     * Fetches all recent changes to origin in the working bare repository.
     *
     * @return void
     */
    protected function updateWorkRepo()
    {
        $process = $this->runGitCommand([
            'fetch',
            'origin',
            'refs/heads/*:refs/heads/*'
        ]);

        if (!$process->isSuccessful()) {
            $this->error(
                'Unable to update work repository in path "' . $this->getGitRepoPath() . '". '
                . $process->getErrorOutput()
            );
        }

        $process = $this->runGitCommand([
            'fetch',
            'origin',
            'refs/tags/*:refs/tags/*'
        ]);

        if (!$process->isSuccessful()) {
            $this->error(
                'Unable to update work repository in path "' . $this->getGitRepoPath() . '". '
                . $process->getErrorOutput()
            );
        } else {
            $this->line(' - Updated work repository.');
        }

        $this->setRemotes();
    }

    /**
     * Sets the remotes for the working repository to point to subsplit repositories.
     *
     * @return void
     */
    protected function setRemotes()
    {
        $process = $this->runGitCommand(['remote']);
        $remotes = preg_split('/[\n\r]+/', trim($process->getOutput()), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($this->remotes as $remote => $split) {
            $process = $this->runGitCommand([
                'remote',
                (in_array($remote, $remotes)) ? 'set-url' : 'add',
                $remote,
                sprintf($split['url'], $this->token)
            ]);
            if (!$process->isSuccessful()) {
                $this->error(
                    ' - Unable to set remote repository for "' . $remote . '" module. '
                    . $process->getErrorOutput()
                );
            } else {
                $this->line(' - Set remote repository for "' . $remote . '" module.');
            }

            $process = $this->runGitCommand([
                'fetch',
                $remote
            ]);
            if (!$process->isSuccessful()) {
                $this->error(
                    ' - Unable to fetch repository for "' . $remote . '" module. ' . $process->getErrorOutput()
                );
            } else {
                $this->line(' - Fetched repository for "' . $remote . '" module.');
            }
        }
    }

    /**
     * Get path to the "splitsh" utility for the current OS, bundled with the app.
     *
     * @return string
     */
    protected function getSplitshPath()
    {
        $phar = Phar::running(true);

        if (empty($phar)) {
            if (PHP_OS_FAMILY === 'Darwin') {
                return $this->getBaseDir('bin/splitsh-lite-mac');
            }

            return $this->getBaseDir('bin/splitsh-lite-unix');
        } else {
            $dataDir = new DataDir();

            if (PHP_OS_FAMILY === 'Darwin') {
                if (!$dataDir->exists('bin/splitsh-lite-mac')) {
                    $dataDir->put(
                        'bin/splitsh-lite-mac',
                        file_get_contents($phar . '/bin/splitsh-lite-mac')
                    );
                }

                $dataDir->chmod('bin/splitsh-lite-mac', 0755);

                return $dataDir->path('bin/splitsh-lite-mac');
            }

            if (!$dataDir->exists('bin/splitsh-lite-unix')) {
                $dataDir->put(
                    'bin/splitsh-lite-unix',
                    file_get_contents($phar . '/bin/splitsh-lite-unix')
                );
            }

            $dataDir->chmod('bin/splitsh-lite-unix', 0755);

            return $dataDir->path('bin/splitsh-lite-unix');
        }
    }
}
