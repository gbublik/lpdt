<?php
namespace GBublik\Lpdt\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Tcp extends CommandInterface
{

    protected function configure()
    {
        $this->setName('tcp')
            ->setDescription('TCP server mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Logger is running in websocket mode</info>');
    }
}