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
        $this->checkPhpVersion();
        $this->checkJsonExtension();
        $this->checkZipExtension();
        $this->checkFilterExtension();
        $this->checkGdExtension();
        $this->checkHashExtension();
        $this->checkMbstringExtension();
        $this->checkXmlExtension();
        $this->checkOpensslExtension();
        $this->checkUrlFopenSetting();
        $this->line();

        if ($this->failed) {
            $this->error(
                'Your current environment does not support October CMS. Please review the checklist above and follow'
                . ' the suggestions in order to allow compatibility.'
            );
        } elseif ($this->warned) {
            $this->warn(
                'October CMS is compatible with your environment, but certain features may not work or may work'
                . ' incorrectly. Please review the checklist above and follow the suggestions in order to improve'
                . ' compatibility.'
            );
        } else {
            $this->success('October CMS is fully compatible with your environment.');
        }
    }

    protected function checkPhpVersion()
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

    protected function checkJsonExtension()
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

    protected function checkGdExtension()
    {
        $this->doCheck('The "gd" extension is installed.');

        if (!extension_loaded('gd')) {
            $this->checkFailed(
                'The "gd" extension is missing.',
                'Please install it, or recompile PHP with "--with-gd".'
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

    protected function checkMbstringExtension()
    {
        $this->doCheck('The "mbstring" extension is installed.');

        if (!extension_loaded('mbstring')) {
            $this->checkFailed(
                'The "mbstring" extension is missing.',
                'Please install it, or recompile PHP with "--enable-mbstring".'
            );
        } else {
            $this->checkSuccessful();
        }
    }

    protected function checkXmlExtension()
    {
        $this->doCheck('The "SimpleXML" extension is installed.');

        if (!class_exists('SimpleXMLElement')) {
            $this->checkFailed(
                'The "SimpleXML" extension is missing.',
                'Please install it, or recompile PHP without "--disable-simplexml".'
            );
        } else {
            $this->checkSuccessful();
        }
    }

    protected function checkOpensslExtension()
    {
        $this->doCheck('The "openssl" extension is installed and can support TLSv1.1 or TLSv1.2 connections.');

        if (!extension_loaded('openssl')) {
            $this->checkWarned(
                'The "openssl" extension is missing.',
                'This will prevent secure transfers, such as plugin updates, from being able to be made.',
                'If possible, you should enable it or recompile PHP with "--with-openssl"'
            );
        } elseif (OPENSSL_VERSION_NUMBER < 0x1000100f) {
            // Attempt to parse version number out, fallback to whole string value.
            $opensslVersion = trim(strstr(OPENSSL_VERSION_TEXT, ' '));
            $opensslVersion = substr($opensslVersion, 0, strpos($opensslVersion, ' '));
            $opensslVersion = $opensslVersion ? $opensslVersion : OPENSSL_VERSION_TEXT;

            $this->checkWarned(
                'The OpenSSL library ('.$opensslVersion.') used by PHP does not support TLSv1.2 or TLSv1.1.',
                'If possible you should upgrade OpenSSL to version 1.0.1 or above.'
            );
        } else {
            $this->checkSuccessful();
        }
    }

    protected function checkUrlFopenSetting()
    {
        $this->doCheck('The "allow_url_fopen" setting is enabled.');

        if (!ini_get('allow_url_fopen')) {
            $this->checkWarned(
                'The allow_url_fopen setting is incorrect.',
                'This will prevent transfers, such as plugin updates, from being able to be made.',
                'Add the following to the end of your `php.ini`:',
                '    allow_url_fopen = On'
            );
        } else {
            $this->checkSuccessful();
        }
    }
}
