<?php namespace BennoThommo\OctoberCli\Traits;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

trait CheckboxList
{
    /** @var OutputInterface Output interface */
    protected $output;

    /** @var ConsoleSectionOutput Current checkbox */
    protected $checkbox = null;

    /** @var string Current checkbox text */
    protected $checkboxText = null;

    /** @var bool If any checks have failed */
    protected $failed = false;

    /** @var bool If any checks have been warned */
    protected $warned = false;

    /**
     * Writes out a checkbox item to console.
     *
     * @param [type] $text
     * @return void
     */
    protected function doCheck($text)
    {
        $this->checkbox = $this->output->section();
        $this->checkboxText = $text;
        $this->checkbox->writeln('[ ] ' . $text);
    }

    protected function checkFailed()
    {
        $this->checkbox->overwrite('[<error>X</error>] ' . $this->checkboxText);
        if (func_num_args() > 0) {
            $this->checkbox->writeln("<comment>      " . implode("\n      ", func_get_args()) . '</comment>');
        }
        $this->failed = true;
    }

    protected function checkWarned()
    {
        $this->checkbox->overwrite('[<warn>W</warn>] ' . $this->checkboxText);
        if (func_num_args() > 0) {
            $this->checkbox->writeln("<comment>      " . implode("\n      ", func_get_args()) . '</comment>');
        }
        $this->warned = true;
    }

    protected function checkSuccessful()
    {
        $this->checkbox->overwrite('[<success>✓</success>] ' . $this->checkboxText);
        if (func_num_args() > 0) {
            $this->checkbox->writeln("<comment>      " . implode("\n      ", func_get_args()) . '</comment>');
        }
    }
}
