<?php
namespace GBublik\Lpdt\Socket;


class TcpClient implements ClientInterface
{
    /** @var string */
    public $host;

    /** @var integer */
    public $port;

    /** @var resource */
    protected $socket;

    /**
     * Клиент tcp соединения
     * @param resource $socket
     */
    public function __construct(&$socket)
    {
        $this->socket = $socket;
        socket_getpeername($socket, $this->host, $this->port);
    }

    public function write(string $message)
    {

    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function disconnect()
    {
        @socket_close($this->socket);
    }

    /**
     * @param string $message
     * @return mixed|void
     * @throws ClientErrorException
     */
    public function send(string $message)
    {
        @socket_write($this->socket, $message, strlen($message));
        if ($err = @socket_last_error($this->socket)) {
            throw new ClientErrorException(sprintf(
                'Client %s:%d error: [%s]%s',
                $this->host,
                $this->port,
                $err,
                socket_strerror($err)
            ));
        }
    }

    /**
     * @return string|null
     * @throws ClientErrorException
     */
    public function read()
    {
        $result = @socket_read($this->socket, 2048, PHP_NORMAL_READ);
        if (empty($result) && $err = socket_last_error($this->socket)) {
            throw new ClientErrorException(sprintf(
                'Client %s:%d error: [%s]%s',
                $this->host,
                $this->port,
                $err,
                socket_strerror($err)
            ));
            socket_close($this->socket);
        }
        return $result;
    }
}