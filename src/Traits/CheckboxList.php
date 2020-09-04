<?php namespace BennoThommo\OctoberCli\Traits;

use Symfony\Component\Console\Output\OutputInterface;

trait CheckboxList
{
    /** @var OutputInterface Output interface */
    protected $output;

    /**
     * Writes out a checkbox item to console.
     *
     * @param [type] $text
     * @return void
     */
    protected function doCheck($text)
    {
        $this->section = $this->output->section();
        $this->sectionText = $text;
        $this->section->writeln('[ ] ' . $text);
    }

    protected function checkFailed()
    {
        $this->section->overwrite('[<error>X</error>] ' . $this->sectionText);
        if (func_num_args() > 0) {
            $this->section->writeln("<comment>      " . implode("\n      ", func_get_args()) . '</comment>');
        }
        $this->failed = true;
    }

    protected function checkWarned()
    {
        $this->section->overwrite('[<warn>W</warn>] ' . $this->sectionText);
        if (func_num_args() > 0) {
            $this->section->writeln("<comment>      " . implode("\n      ", func_get_args()) . '</comment>');
        }
        $this->warned = true;
    }

    protected function checkSuccessful()
    {
        $this->section->overwrite('[<success>✓</success>] ' . $this->sectionText);
        if (func_num_args() > 0) {
            $this->section->writeln("<comment>      " . implode("\n      ", func_get_args()) . '</comment>');
        }
    }
}
