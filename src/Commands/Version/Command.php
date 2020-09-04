<?php namespace BennoThommo\OctoberCli\Commands\Version;

use BennoThommo\OctoberCli\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Version command
 *
 * @since 0.1.0
 * @author Ben Thomson
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

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manifest = new FileManifest($input->getArgument('path'));

        $this->comment('Detecting October CMS build...');

        $source = new SourceManifest();
        $build = $source->compare($manifest, $input->getOption('detailed'));

        if (!$build['confident']) {
            $this->warn('We could not accurately determine your October CMS build due to the number of modifications. The closest detected build is October CMS build ' . $build['build'] . '.');
        } else if ($build['modified']) {
            $this->info('Detected a modified version of October CMS build ' . $build['build'] . '.');
        } else {
            $this->info('Detected October CMS build ' . $build['build'] . '.');
        }

        if ($input->getOption('detailed') && $build['modified']) {
            $this->line('');
            $this->comment('We have detected the following modifications:');

            if (count($build['changes']['added'] ?? [])) {
                $this->line('');
                $this->info('Files added:');

                foreach (array_keys($build['changes']['added']) as $file) {
                    $this->line(' - ' . $file);
                }
            }

            if (count($build['changes']['modified'] ?? [])) {
                $this->line('');
                $this->info('Files modified:');

                foreach (array_keys($build['changes']['modified']) as $file) {
                    $this->line(' - ' . $file);
                }
            }

            if (count($build['changes']['removed'] ?? [])) {
                $this->line('');
                $this->info('Files removed:');

                foreach ($build['changes']['removed'] as $file) {
                    $this->line(' - ' . $file);
                }
            }
        }
    }
}
