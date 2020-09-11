<?php namespace BennoThommo\OctoberCli;

use RuntimeException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

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
    /** @var InputInteface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /**
     * @inheritDoc
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
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

    /**
     * Prompts the user for an answer to a question.
     *
     * @param string $text
     * @param mixed $default
     * @param bool $hidden
     * @return mixed
     */
    protected function prompt(string $question, $default = null, bool $hidden = false)
    {
        $askHelper = $this->getHelper('question');

        $prompt = PHP_EOL . ' ' . $question;
        if (!is_null($default)) {
            $prompt .= ' [<info>' . (string) $default . '</info>]';
        }
        $prompt .= PHP_EOL . ' > ';

        $questionObj = new Question($prompt, $default);
        if ($hidden) {
            $questionObj->setHidden(true);
            $questionObj->setHiddenFallback(false);
        }

        return $askHelper->ask(
            $this->input,
            $this->output,
            $questionObj
        );
    }

    /**
     * Prompts the user for a number as an answer to a question
     *
     * @param string $text
     * @param int|null $default
     * @return int
     */
    protected function promptInt(string $question, ?int $default = null): int
    {
        $askHelper = $this->getHelper('question');

        $prompt = PHP_EOL . ' ' . $question;
        if (!is_null($default)) {
            $prompt .= ' [<info>' . (string) $default . '</info>]';
        }
        $prompt .= PHP_EOL . ' > ';

        $questionObj = new Question($prompt, $default);
        $questionObj->setValidator(function ($answer) {
            if (!preg_match('/^(0|-?[1-9][0-9]*)$/', $answer)) {
                throw new RuntimeException('Value must be a number');
            }

            return $answer;
        });
        $questionObj->setMaxAttempts(2);

        return (int) $askHelper->ask(
            $this->input,
            $this->output,
            $questionObj
        );
    }
}
