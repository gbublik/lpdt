<?php
namespace GBublik\Lpdt\Commands;

use GBublik\Lpdt\Socket\TcpServer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
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
        $tcpServer = new TcpServer('localhost', 8070);
        $tcpServer->run();
        /** @var ConsoleSectionOutput $section */
        $section = $output->section();
        $i =0;
        while (true) {
            $i++;
            $tcpServer->accept();

            $section->clear();
            $section->writeln('Clients: ' . count($tcpServer->connections));

            sleep(1);
        }

        $output->writeln('<info>Logger is running in websocket mode</info>');
    }
}