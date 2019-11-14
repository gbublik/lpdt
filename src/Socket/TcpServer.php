<?php
namespace GBublik\Lpdt\Socket;

use Exception;

/**
 * Nonblock socket server
 * @package GBublik\Lpdt\Socket
 */
class TcpServer
{
    /** @var resource */
    protected $socket;

    /** @var string  */
    protected $hostname;

    /** @var int  */
    protected $port;

    /** @var bool  */
    protected $isRun = false;

    /** @var ClientInterface[] */
    public $connections = [];

    public function __construct(string $hostname, int $port)
    {
        $this->hostname = $hostname;
        $this->port = $port;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function run()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        @socket_bind($this->socket, $this->hostname, $this->port);
        if ($error = socket_last_error($this->socket)) {
            throw new Exception(sprintf('Не удалось запустить TCP сервер: [%s] %s', $error, socket_strerror($error)));
        }
        socket_listen($this->socket);
        socket_set_nonblock($this->socket);

        $this->isRun = true;

        return true;
    }

    /**
     * @return bool|void
     */
    public function stop()
    {
        if (!$this->isRun) return;

        foreach ($this->connections as $connection) $connection->disconnect();

        socket_close($this->socket);

        $this->isRun = false;
        return true;
    }

    public function accept()
    {
        $client = null;

        if ($socket = socket_accept($this->socket)) {
            $client = new TcpClient($socket);
            array_push($this->connections, $client);
        }
        return $client;
    }

    public function isRun()
    {
        return $this->isRun;
    }

    protected function getConnections()
    {
        return $this->connections;
    }
}