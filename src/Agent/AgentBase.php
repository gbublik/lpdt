<?php

namespace GBublik\Lpdt\Agent;

use GBublik\Lpdt\Config;
use GBublik\Lpdt\Request;

/** Base client agent */
abstract class AgentBase
{
    /** @var resource Client socket */
    protected $socket = null;

    /** @var Request */
    protected $request;

    /** @var Config */
    protected $config;

    protected $memoryLimit;

    public function __construct(&$socket, Request $request, Config $config)
    {
        $this->socket = $socket;
        $this->request = $request;
        $this->config = $config;
        $this->memoryLimit = ini_get('memory_limit');
    }

    /**
     * @param array $serverStatistic
     * @return mixed
     * @throws AgentException
     */
    abstract public function tick(&$serverStatistic = []);

    /**
     * @param $str
     * @return mixed
     * @throws AgentException
     */
    abstract public function write($str, $serverInfo);

    abstract public function getSocket();

    abstract public function disconnect($msg = 'Disconnect');
}