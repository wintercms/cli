<?php namespace Winter\Cli\Commands\ProjectVersion;

use Winter\Cli\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Winter\Cli\Filesystem\FileManifest;
use Winter\Cli\Filesystem\SourceManifest;

/**
 * Project version command
 *
 * @since 0.2.1 Renamed to "project:version" (previously "version")
 * @since 0.1.0
 * @author Ben Thomson
 */
class Command extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected static $defaultName = 'project:version';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Determines the version of Winter CMS in use in a project.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'This command allows you to scan an Winter CMS project and determine the version (build)'
                . ' in use.'
            )

            // arguments
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The path to the Winter CMS project.'
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

        $this->comment('Detecting Winter CMS build...', OutputInterface::VERBOSITY_VERBOSE);

        $source = new SourceManifest();
        $build = $source->compare($manifest, $input->getOption('detailed'));

        if (!$build['confident']) {
            $this->warn(
                'We could not accurately determine your Winter CMS build due to the number of modifications. The' .
                ' closest detected build is Winter CMS build ' . $build['build'] . '.',
                OutputInterface::VERBOSITY_QUIET
            );
        } elseif ($build['modified']) {
            $this->info(
                'Detected a modified version of Winter CMS build ' . $build['build'] . '.',
                OutputInterface::VERBOSITY_QUIET
            );
        } else {
            $this->info(
                'Detected Winter CMS build ' . $build['build'] . '.',
                OutputInterface::VERBOSITY_QUIET
            );
        }

        if ($input->getOption('detailed') && $build['modified']) {
            $this->line('', OutputInterface::VERBOSITY_QUIET);
            $this->comment('We have detected the following modifications:', OutputInterface::VERBOSITY_QUIET);

            if (count($build['changes']['added'] ?? [])) {
                $this->line('', OutputInterface::VERBOSITY_QUIET);
                $this->info('Files added:', OutputInterface::VERBOSITY_QUIET);

                foreach (array_keys($build['changes']['added']) as $file) {
                    $this->line(' - ' . $file, OutputInterface::VERBOSITY_QUIET);
                }
            }

            if (count($build['changes']['modified'] ?? [])) {
                $this->line('', OutputInterface::VERBOSITY_QUIET);
                $this->info('Files modified:', OutputInterface::VERBOSITY_QUIET);

                foreach (array_keys($build['changes']['modified']) as $file) {
                    $this->line(' - ' . $file, OutputInterface::VERBOSITY_QUIET);
                }
            }

            if (count($build['changes']['removed'] ?? [])) {
                $this->line('', OutputInterface::VERBOSITY_QUIET);
                $this->info('Files removed:', OutputInterface::VERBOSITY_QUIET);

                foreach ($build['changes']['removed'] as $file) {
                    $this->line(' - ' . $file, OutputInterface::VERBOSITY_QUIET);
                }
            }
        }
    }
}
