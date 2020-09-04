<?php namespace BennoThommo\OctoberCli\Commands\InstallCheck;

use BennoThommo\OctoberCli\BaseCommand;
use BennoThommo\OctoberCli\Traits\CheckboxList;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check environment command
 *
 * @since 0.2.0
 * @author Ben Thomson
 */
class Command extends BaseCommand
{
    use CheckboxList;

    /**
     * @inheritDoc
     */
    protected static $defaultName = 'install:check';

    /** @var Symfony\Component\Console\Output\ConsoleSectionOutput Current section */
    protected $section = null;

    /** @var string Current section text */
    protected $sectionText = null;

    /** @var bool If any checks have failed */
    protected $failed = false;

    /** @var bool If any checks have been warned */
    protected $warned = false;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Checks the current environment that it can run October CMS.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to check that your current environment can run October CMS.')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->line();
        $this->checkPHPVersion();
        $this->checkJSONExtension();
        $this->checkZipExtension();
        $this->checkFilterExtension();
    }

    protected function checkPHPVersion()
    {
        $this->doCheck('Installed PHP version is 7.2.9 or higher.');

        if (version_compare(PHP_VERSION, '7.2.9', '<')) {
            $this->checkFailed(
                'Your PHP version (' . PHP_VERSION . ') is outdated and not supported by October CMS.',
                'Please upgrade your PHP version to at least 7.2.9.'
            );
        } elseif (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            $this->checkWarned(
                'Your PHP version (' . PHP_VERSION . ') is untested with October CMS.',
                'You may experience errors and potential data loss.'
            );
        } else {
            $this->checkSuccessful();
        }
    }

    protected function checkJSONExtension()
    {
        $this->doCheck('The "json" extension is installed.');

        if (!function_exists('json_decode')) {
            $this->checkFailed(
                'The "json" extension is missing.',
                'Please install it, or recompile PHP without "--disable-json".'
            );
        } else {
            $this->checkSuccessful();
        }
    }

    protected function checkZipExtension()
    {
        $this->doCheck('The "zip" extension is installed.');

        if (!class_exists('ZipArchive')) {
            $this->checkFailed(
                'The "zip" extension is missing.',
                'Please install it, or recompile PHP with "--enable-zip".'
            );
        } else {
            $this->checkSuccessful();
        }
    }

    protected function checkFilterExtension()
    {
        $this->doCheck('The "filter" extension is installed.');

        if (!extension_loaded('filter')) {
            $this->checkFailed(
                'The "filter" extension is missing.',
                'Please install it, or recompile PHP without "--disable-filter".'
            );
        } else {
            $this->checkSuccessful();
        }
    }

    protected function checkHashExtension()
    {
        $this->doCheck('The "hash" extension is installed.');

        if (!extension_loaded('hash')) {
            $this->checkFailed(
                'The "hash" extension is missing.',
                'Please install it, or recompile PHP without "--disable-hash".'
            );
        } else {
            $this->checkSuccessful();
        }
    }
}
