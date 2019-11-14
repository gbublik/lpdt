<?php
namespace GBublik\Lpdt\Commands;

use Exception;
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
            ->setDescription('Debug mode');
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