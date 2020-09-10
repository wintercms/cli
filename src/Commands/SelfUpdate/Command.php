<?php namespace BennoThommo\OctoberCli\Commands\SelfUpdate;

use Exception;
use Phar;
use BennoThommo\OctoberCli\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Self update command
 *
 * @since 0.2.0
 * @author Ben Thomson
 */
class Command extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected static $defaultName = 'self-update';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // aliases
            ->setAliases(['selfupdate'])

            // the short description shown while running "php bin/console list"
            ->setDescription('Self-updates the October CLI helper.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command will check and upgrade the October CLI helper.')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $phar = Phar::running(false);
        if (empty($phar)) {
            throw new Exception('You must be running October CLI as a PHAR executable in order to self-update.');
        }

        $github = new \Github\Client();
        $release = $github->api('repo')->releases()->latest('bennothommo', 'october-cli');
        $currentVersion = str_replace('v', '', '@version@');
        $latestVersion = str_replace('v', '', $release['tag_name']);

        if (version_compare($currentVersion, $latestVersion, '>=')) {
            $this->success('You already have the latest version of October CLI', OutputInterface::VERBOSITY_QUIET);
            return 0;
        }

        // Check that this PHAR is writable
        if (!is_writable($phar)) {
            throw new Exception(
                'You do not have write permissions to self-update October CLI. You may need to run'
                . ' to run this command as a privileged user.'
            );
        }

        // Find october.phar in latest release
        $newPhar = false;
        foreach ($release['assets'] as $asset) {
            if ($asset['name'] === 'october.phar' && $asset['content_type'] === 'application/phar') {
                $newPhar = $asset['browser_download_url'];
            }
        }

        if (!$newPhar) {
            throw new Exception('Unable to find PHAR file for the latest release of October CLI');
        }

        $this->comment('Downloading latest version of October CLI (v' . $latestVersion . ')');

        try {
            file_put_contents($phar, file_get_contents($newPhar));
        } catch (Throwable $e) {
            throw new Exception('Unable to download new version of October CLI - ' . $e->getMessage());
        }

        $this->success('Updated October CLI to v' . $latestVersion, OutputInterface::VERBOSITY_QUIET);
    }
}
