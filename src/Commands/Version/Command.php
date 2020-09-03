<?php
namespace BennoThommo\OctoberCli\Commands\Version;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Version command
 *
 * @package BennoThommo\OctoberCli\Commands\Version
 */
class Command extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected static $defaultName = 'version';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Determines the version of October CMS in use.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'This command allows you to scan an October CMS installation and determine the version (build)'
                . ' in use.'
            )

            // arguments
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The path to the October CMS installation.'
            )

            // options
            ->addOption(
                'detailed',
                'd',
                InputOption::VALUE_NONE,
                'Lists modified, created or deleted files.'
            )
        ;
    }
}
