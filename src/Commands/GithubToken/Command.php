<?php namespace BennoThommo\OctoberCli\Commands\GithubToken;

use BennoThommo\OctoberCli\BaseCommand;
use BennoThommo\OctoberCli\GitHub\Token;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * GitHub token command
 *
 * @since 0.2.1
 * @author Ben Thomson
 */
class Command extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected static $defaultName = 'github:token';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Sets the GitHub Access token for the helper.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Allows you to store a GitHub Access Token locally. This is used for some functions of the CLI helper'
                . ' which may be subject to GitHub API\'s rate limits.' . "\n\n"
                . 'If you wish to create the token necessary, please visit the following URL:' . "\n"
                . 'https://github.com/settings/tokens/new?scopes=public_repo&description=October%20CLI%20Helper'
            )

            // arguments
            ->addArgument(
                'token',
                InputArgument::REQUIRED,
                'The GitHub Access Token to store'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $token = new Token();
        $path = $token->write($input->getArgument('token'));

        $this->success('Token written to ' . $path);
    }
}
