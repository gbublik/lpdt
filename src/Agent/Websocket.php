<?php
namespace GBublik\Lpdt\Agent;

use GBublik\Lpdt\Server\Config;
use GBublik\Lpdt\Server\Request;

class Websocket extends AgentBase
{
    public function __construct($socket, Request $request, Config $config)
    {
        parent::__construct($socket, $request, $config);
        $this->handshake();
    }

    public function tick(&$serverStatistic = [])
    {
        // TODO: Implement tick() method.
    }

    public function write($str, $serverInfo)
    {
        // TODO: Implement write() method.
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function disconnect($msg = 'Disconnect')
    {
        // TODO: Implement disconnect() method.
    }

    public function handshake()
    {
        print_r($this->request);
        exit();
    }
}