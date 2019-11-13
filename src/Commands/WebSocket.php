<?php
namespace GBublik\Lpdt\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebSocket extends CommandInterface
{

    protected function configure()
    {
        $this->setName('websocket')
            ->setDescription('Websocket server mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Logger is running in websocket mode</info>');
    }
}