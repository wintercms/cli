<?php namespace BennoThommo\OctoberCli;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base command.
 *
 * All commands in this CLI helper should extend this. Provides some helper functions and styling.
 *
 * @since 0.1.0
 * @author Ben Thomson
 */
class BaseCommand extends SymfonyCommand
{
    /** @var OutputInterface Output interface */
    protected $output;

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        // Add success style
        $outputStyle = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('success', $outputStyle);

        // Add warning style
        $outputStyle = new OutputFormatterStyle('yellow');
        $output->getFormatter()->setStyle('warn', $outputStyle);

        return parent::run($input, $output);
    }

    /**
     * Write a line to output.
     *
     * @param string $text
     * @return void
     */
    protected function line($text = '')
    {
        $this->output->writeln($text);
    }

    /**
     * Display a comment.
     *
     * @param string $text
     * @return void
     */
    protected function comment($text)
    {
        $this->output->writeln('<comment>' . $text . '</comment>');
    }

    /**
     * Display an informational message.
     *
     * @param string $text
     * @return void
     */
    protected function info($text)
    {
        $this->output->writeln('<info>' . $text . '</info>');
    }

    /**
     * Display a success message.
     *
     * @param string $text
     * @return void
     */
    protected function success($text)
    {
        $this->output->writeln('<success>' . $text . '</success>');
    }

    /**
     * Display a warning message.
     *
     * @param string $text
     * @return void
     */
    protected function warning($text)
    {
        $this->output->writeln('<warn>' . $text . '</warn>');
    }

    /**
     * Display a warning message.
     *
     * @param string $text
     * @return void
     */
    protected function warn($text)
    {
        $this->warning($text);
    }

    /**
     * Display an error message.
     *
     * @param string $text
     * @return void
     */
    protected function error($text)
    {
        $this->output->writeln('<error>' . $text . '</error>');
    }
}
