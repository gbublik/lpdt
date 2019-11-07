<?php
use GBublik\Supervisor\AsyncSocketServer;

class AsyncSocketServerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AsyncSocketServer */
    protected $socket = null;

    public function setUp()
    {
        $this->socket = new AsyncSocketServer('localhost', 19001);
    }

    public function testSetterGetter()
    {
        $socket = new AsyncSocketServer();
        $this->assertEquals(8082, $socket->getPort());
        $this->assertEquals('localhost', $socket->getHost());
        $socket->setHost('127.0.0.1')->setPort(19000);
        $this->assertEquals(19000, $socket->getPort());
        $this->assertEquals('127.0.0.1', $socket->getHost());
    }

    /**
     * @depends testSetterGetter
     */
    public function testStartStopServer()
    {
        $this->socket->start();
        $this->assertTrue($this->socket->isRun());
        $fp = $this->createSocketConnecting();
        $this->assertNotEmpty($fp);
        fclose($fp);

        $this->socket->stop();
        $this->assertFalse($this->socket->isRun());
        $fp = @stream_socket_client("tcp://localhost:19001", $errno, $errstr);
        $this->assertEmpty($fp);
        if ($fp) fclose($fp);
    }

    /**
     * @depends testStartStopServer
     */
    public function testListenerNewConnections()
    {
        $this->socket->start();
        $fp = $this->createSocketConnecting();
        $this->assertNotEmpty($fp);
        fwrite($fp, "Upgrade: SupervisorClient\r\n");

        $this->socket->listnerNewConnections();
        $this->assertEquals(1, count($this->socket->getAgents()));
        fclose($fp);
        $this->socket->stop();
    }

    protected function createSocketConnecting()
    {
        return stream_socket_client("tcp://localhost:19001", $errno, $errstr);
    }
}
