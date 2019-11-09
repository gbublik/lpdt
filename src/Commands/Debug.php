<?php
namespace GBublik\Lpdt\Commands;

use GBublik\Lpdt\Writer\DebugWriter;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setDescription('Debug mode Used to debug php scripts in the console. This mode is started by default.')
            ->addOption(
                'stack-error',
                '-e',
                InputOption::VALUE_OPTIONAL,
                'Number of lines for error output',
                5
            )->addOption(
                'stack-write',
                '-w',
                InputOption::VALUE_OPTIONAL,
                'Number of lines for message output',
                1
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=black;bg=magenta>Lpdt is running in debug mode</>');

        call_user_func([$this->handler, 'execute'], new DebugWriter($input, $output));
    }
}