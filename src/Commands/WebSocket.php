<?php
namespace GBublik\Lpdt\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class WebSocket extends Command
{

    protected function configure()
    {
        $this->setName('websocket')
            ->setDescription('Режим websocket сервера');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Logger is running in websocket mode</info>');
    }
}