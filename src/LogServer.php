<?php

namespace GBublik\Lpdt;

use GBublik\Lpdt\Agent;
use GBublik\Lpdt\Agent\AgentFactory;

declare(ticks=3000);

/**
 * Class object for create noblock socket server
 * @package GBublik\Lpdt
 */
class LogServer
{
    /** @var resource Socket server */
    protected $socket = null;

    /** @var array clients poll */
    protected $clients = [];

    /** @var string Socket server name */
    protected $host = null;

    /** @var int Socket server port */
    protected $port = null;

    protected $isRun = false;

    /** @var resource */
    protected $console;

    /** @var int */
    protected $tickCounter = 0;

    /** @var self */
    protected static $instance;

    /** @var Config */
    protected $config;

    protected $serverInfo = [
        'start_time' => null,
        'current_step' => null,
        'executed_step' => [],
        'error' => [],
        'pick_memory_usage' => 0
    ];

    protected $onlyError = false;

    protected $memoryLimit;

    protected function __construct($host = null, $port = null, Config $config = null)
    {
        $this->console = fopen('php://stdout', 'w');
        $this->config = $config ?: new Config();

        $this->setHost($host ?: $this->config['default_host'])
            ->setPort($port ?: $this->config['default_port']);

        $this->serverInfo['start_time'] = new \DateTime();
        $this->memoryLimit = $this->getMaxMemory();
        $this->start();
    }

    public static function getInstance($host = null, $port = null, Config $config = null)
    {
        if (empty(self::$instance)) {
            self::$instance = new self($host, $port, $config);
            register_tick_function([&self::$instance, 'tickHandler'], true);
            register_shutdown_function ([&self::$instance, 'shutdown'], true);
        }
        return self::$instance;
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Run server
     * @return LogServer
     * @throws \Exception
     */
    public function start()
    {
        if ($this->isRun()) return self::$instance;
        if (!empty(self::$instance)) register_tick_function([&self::$instance, 'tickHandler'], true);
        if (!empty($this->config['socket_enabled'])) {
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_bind($this->socket, $this->getHost(), $this->getPort());
            socket_listen($this->socket);
            socket_set_nonblock($this->socket);
            $this->createFatalErrorBySocket($this->socket);

            $this->isRun = true;

            $this->writeConsole("LPDT: Run debug socket server: " . $this->getHost() . ":" . $this->getPort());
        } else {
            $this->writeConsole("LPDT: Socket server is disabled: " . $this->getHost() . ":" . $this->getPort());
        }

        return self::$instance;
    }

    public function stop()
    {
        if ($this->isRun) {
            socket_close($this->socket);
            $this->isRun = false;
            $this->writeConsole("Stop debug server");
        }
        @unregister_tick_function([&self::$instance, 'tickHandler']);
        @fclose($this->console);
        $this->writeToLog();
        return self::$instance;
    }

    protected function writeToLog()
    {
        if ($this->config['log']['enabled'] === true && !empty($this->config['log']['file'])) {
            $this->serverInfo['memory_limit'] = ini_get('memory_limit');
            file_put_contents(
                $this->config['log']['file'],
                json_encode($this->serverInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\r\n",
                $this->config['log']['append'] ? FILE_APPEND : null
            );
        }
    }

    public function onlyError($mode = true)
    {
        $this->onlyError = $mode;
    }

    /**
     * Return True is server started
     * @return bool
     */
    public function isRun()
    {
        return $this->isRun;
    }

    public function listenerNewConnections()
    {
        if ($socket = @socket_accept($this->socket)) {
            if ($agent = AgentFactory::create($socket, $this->config)) {
                socket_getpeername($socket, $host, $port);
                $this->clients[] = [
                    'host' => $host ?: '<empty>',
                    'port' => $port ?: '<empty>',
                    'agent' => &$agent
                ];

                $this->writeConsole("LPDT: New connection: " . ($host ?: '<empty>') . ":" . ($port ?: '<empty>'));
            }
        }
    }

    function tickHandler()
    {
        $this->tickCounter++;
        if ($this->tickCounter % $this->config['tick_scale'] == 0 && $this->isRun()) {
            $this->listenerNewConnections();
            foreach ($this->clients as $key => $client) {
                try {
                    /** @var Agent\AgentBase $agent */
                    $agent = &$client['agent'];
                    $agent->tick($this->serverInfo);
                } catch (Agent\AgentException $e) {
                    $this->writeConsole(
                        'LPDT: ' . $e->getMessage() . '('.$e->getCode().'): ' .
                        $this->clients[$key]['host'] .
                        $this->clients[$key]['port']
                    );
                    unset($this->clients[$key]);
                }
            }
        }
        fwrite($this->console, $this->loading());
        $memoryUsage = memory_get_peak_usage();
        if ($memoryUsage > $this->serverInfo['pick_memory_usage']) $this->serverInfo['pick_memory_usage'] = $memoryUsage;
    }

    public function shutdown()
    {
        $lastError = error_get_last();
        if ($lastError['type'] == 1) {
            $this->serverInfo['error'][] = 'PHP ERROR: ' . $lastError['message'];
            $this->serverInfo['error'][] = 'PHP FILE: ' . $lastError['file'] . ':' . $lastError['line'];
        } else {
            $this->serverInfo['executed_step'][] = $this->serverInfo['current_step'];
            $this->serverInfo['current_step'] = null;
        }
        $this->serverInfo['pick_memory_usage'] = $this->convert($this->serverInfo['pick_memory_usage']);
        $this->stop();
    }

    public function error($str, $stop = false)
    {
        $this->serverInfo['error'][] = ($this->serverInfo['current_step'] ? $this->serverInfo['current_step'] . ': ' : null) . $str;
        $this->write("Ошибка: " . $str, true);
        if ($stop) {
            $this->stop();
            die();
        }
    }

    public function step($str)
    {
        if ($str != $this->serverInfo['current_step']) {
            if ($this->serverInfo['current_step']) $this->serverInfo['executed_step'][] = $this->serverInfo['current_step'];
            $this->serverInfo['current_step'] = $str;
            $this->write( 'New step instance');
        }
    }

    public function write($str, $isError = false)
    {
        $this->listenerNewConnections();
        if ($this->isRun() && (!$this->onlyError || ($this->onlyError && $isError))) {
            foreach ($this->clients as $key => $client) {
                try {
                    /** @var Agent\AgentBase $agent */
                    $agent = $client['agent'];
                    $agent->write($str, $this->serverInfo);
                } catch (Agent\AgentException $e)
                {
                    $this->writeConsole(
                        'LPDT: ' . $e->getMessage() . '('.$e->getCode().'): ' .
                        $this->clients[$key]['host'] .
                        $this->clients[$key]['port']
                    );
                    unset($this->clients[$key]);
                }catch (RequestException $e)
                {
                    $agent->write('Bad request', $this->serverInfo);
                    $this->writeConsole(
                        'LPDT: Ошибка запроса: ' . $e->getMessage() . '('.$e->getCode().'): ' .
                        $this->clients[$key]['host'] .
                        $this->clients[$key]['port']
                    );
                    unset($this->clients[$key]);
                }
            }
        } else if (!$this->onlyError || ($this->onlyError && $isError)) {
            $this->writeConsole(($this->serverInfo['current_step'] ? $this->serverInfo['current_step'] . ': ' : '') . $str);
        }
    }

    protected function writeConsole($str)
    {
        fwrite($this->console, "\r" . $str . "\n");
    }

    /**
     * @return Agent\AgentBase[]
     */
    public function getAgents()
    {
        return $this->clients;
    }

    protected function getSocketError(&$socket)
    {
        $error = socket_last_error($socket);
        if ($error > 0) return socket_strerror($error);
        return null;
    }

    protected function createFatalErrorBySocket(&$socket)
    {
        if ($error = $this->getSocketError($socket)) {
            throw new \Exception($error);
        }
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return self
     */
    public function setPort($port)
    {
        $this->port = (int)$port;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return self
     */
    public function setHost($host)
    {
        $this->host = (string)$host;
        return $this;
    }

    protected function loading()
    {
        static $current;
        $response = '';

        switch ($current) {
            case 0:
                $response = '|';
                break;
            case 1:
                $response = '/';
                break;
            case 2:
                $response = '-';
                break;
            case 3:
                $response = '\\';
                break;
            case 4:
                $response = '-';
                break;
        }
        if ($current == 4) $current = 0;
        else $current++;
        return "\r" . $response;
    }

    protected function getMaxMemory()
    {
        return str_replace(
            ['B', 'K', 'M', 'G'],
            ['', '000', '000000', '000000000000'],
            ini_get('memory_limit')
        );
    }

    protected function convert($num)
    {
        $neg = $num < 0;
        $units = array('B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');

        if ($neg) {
            $num = -$num;
        }
        if ($num < 1) {
            return ($neg ? '-' : '') . $num . ' B';
        }

        $exponent = min(floor(log($num) / log(1000)), count($units) - 1);
        $num = sprintf('%.02F', ($num / pow(1000, $exponent)));
        $unit = $units[$exponent];

        return ($neg ? '-' : '') . $num . $unit;
    }
}