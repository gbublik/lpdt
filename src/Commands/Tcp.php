<?php
namespace GBublik\Lpdt\Commands;

declare(ticks = 1);

use Exception;
use GBublik\Lpdt\HandlerInterface;
use GBublik\Lpdt\Socket;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Tcp extends CommandInterface
{
    protected $server;

    protected $hostname = 'localhost';

    protected $port = 8090;

    public function __construct(HandlerInterface $handler, array $options = [])
    {
        parent::__construct($handler, $options);
    }

    protected function configure()
    {
        $this->setName('tcp')
            ->setDescription('TCP server mode')
            ->addOption(
                'host',
                '-a',
                InputOption::VALUE_OPTIONAL,
                'Hostname tcp server',
                array_key_exists('hostname', $this->options) ? $this->options['hostname'] : $this->hostname
            )->addOption(
                'port',
                '-p',
                InputOption::VALUE_OPTIONAL,
                'Port tcp server',
                array_key_exists('port', $this->options) ? $this->options['port'] : $this->port
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
        $host = $input->getOption('host') ?? 'localhost';
        $port = $input->getOption('port') ?? 8090;

        global $server;
        $server = new Socket\TcpServer($host, $port);
        $server->run();
        $output->writeln(
            sprintf(
                'TCP server bind on address %s:%d',
                $host,
                $port
            )
        );

        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new Exception('Не удалось породить дочерний процесс');
        } else if ($pid) {
            while(true) {
                if (!$server->isRun() || pcntl_waitpid(-1, $status, WNOHANG) == -1) {
                    $server->stop();
                    $output->writeln('TCP server is stopped.');
                    break;
                }
                if ($client = $server->accept()) {
                    $output->writeln(sprintf('Подключился новый клиент %s:%d', $client->host, $client->port));
                }
                $message = null;
                if (isset($server->connections[0])) {
                    try{
                        $message = $server->connections[0]->read();
                    } catch (Socket\ClientErrorException $e) {
                        $output->writeln('Исчточник отключен: ' . $e->getMessage());
                        unset( $server->connections[0]);
                    }
                }
                if ($message) {
                    foreach ($server->connections as $key=>$connection) {
                        if ($key === 0) continue;
                        try {
                            $connection->send($message);
                        } catch (Socket\ClientErrorException $e) {
                            $output->writeln('Пользователь отключился: ' . $e->getMessage());
                            $server->connections[$key]->disconnect();
                            unset($server->connections[$key]);

                        }
                    }
                }
            }
        } else {
            //call_user_func([$this->handler, 'execute'], new TcpWriter($input, $output));
            $socket  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($socket , '0.0.0.0', 8090);

            $_SERVER['argv'][1] = 'debug';
            if (!in_array('-t', $_SERVER['argv'])) {
                $_SERVER['argv'][] = '-t';
            }
            if (!in_array('-m', $_SERVER['argv'])) {
                $_SERVER['argv'][] = '-m';
            }
            if (!in_array('-n', $_SERVER['argv'])) {
                $_SERVER['argv'][] = '-n';
            }

            $fp = popen('/usr/bin/php ' . implode(' ', $_SERVER['argv']),"r");
            while (!feof($fp)) {
                $buffer = fgets($fp, 4096);
                socket_write($socket, $buffer);
            }
            socket_close($socket);
            pclose($fp);
            $server->stop();
        }
    }
}