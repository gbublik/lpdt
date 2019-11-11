<?php
namespace GBublik\Lpdt\Commands;

use Exception;
use GBublik\Lpdt\Writer\DebugWriter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Debug mode command
 * @package GBublik\Lpdt\Commands
 */
class Debug extends CommandInterface
{
    protected function configure()
    {
        $this->setName('debug')
            ->setDescription('Used to debug php scripts in the console.')
            ->addOption(
                'stack-error',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'Error section size.',
                array_key_exists('stack-error', $this->options) ? $this->options['stack-error'] : null
            )->addOption(
                'stack-message',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Information section size',
                array_key_exists('stack-message', $this->options) ? $this->options['stack-message'] : 1
            )->addOption(
                'log-file',
                '-f',
                InputOption::VALUE_OPTIONAL,
                'Путь к файлу лога',
                array_key_exists('log-file', $this->options) ? $this->options['log-file'] : null
            )->addOption(
                'log-overwrite',
                '-o',
                InputOption::VALUE_OPTIONAL,
                'Перезаписывать лог файл',
                array_key_exists('log-overwrite', $this->options) ? $this->options['log-overwrite'] : true
            )->addOption(
                'log-level',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Уровень логирования. DEBUG, ERROR',
                array_key_exists('log-level', $this->options) ? $this->options['log-level'] : 'DEBUG'
            )->addOption(
                'log-days',
                '-d',
                InputOption::VALUE_OPTIONAL,
                'Сколько дней хранить лог файлы',
                array_key_exists('log-days', $this->options) ? $this->options['log-days'] : 5
            )->addOption(
                'quiet',
                '-q',
                InputOption::VALUE_OPTIONAL,
                'Do not output any message',
                array_key_exists('quiet', $this->options) ? $this->options['quiet'] : false
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        call_user_func([$this->handler, 'execute'], new DebugWriter($input, $output));
    }
}