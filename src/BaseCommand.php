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

        // Add danger style
        $outputStyle = new OutputFormatterStyle('red');
        $output->getFormatter()->setStyle('danger', $outputStyle);

        // Add bold style
        $outputStyle = new OutputFormatterStyle(null, null, [
            'bold'
        ]);
        $output->getFormatter()->setStyle('bold', $outputStyle);

        return parent::run($input, $output);
    }

    /**
     * Write a line to output.
     *
     * @param string $text
     * @param int $verbosity
     * @return void
     */
    protected function line(string $text = '', int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln($text, $verbosity);
    }

    /**
     * Display a comment.
     *
     * @param mixed $text
     * @param int $verbosity
     * @return void
     */
    protected function comment(string $text, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln('<comment>' . $text . '</comment>', $verbosity);
    }

    /**
     * Display an info message.
     *
     * @param string $text
     * @param int $verbosity
     * @return void
     */
    protected function info(string $text, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln('<info>' . $text . '</info>', $verbosity);
    }

    /**
     * Display a success message.
     *
     * @param string $text
     * @param int $verbosity
     * @return void
     */
    protected function success(string $text, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln('<success>' . $text . '</success>', $verbosity);
    }

    /**
     * Display a warning message.
     *
     * @param string $text
     * @param int $verbosity
     * @return void
     */
    protected function warning(string $text, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln('<warn>' . $text . '</warn>', $verbosity);
    }

    /**
     * Display a warning message.
     *
     * @param string $text
     * @param int $verbosity
     * @return void
     */
    protected function warn(string $text, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->warning($text, $verbosity);
    }

    /**
     * Display a danger message.
     *
     * @param string $text
     * @param int $verbosity
     * @return void
     */
    protected function danger(string $text, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln('<danger>' . $text . '</danger>', $verbosity);
    }

    /**
     * Display an error message.
     *
     * @param string $text
     * @param int $verbosity
     * @return void
     */
    protected function error(string $text, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln('<error>' . $text . '</error>', $verbosity);
    }
}
