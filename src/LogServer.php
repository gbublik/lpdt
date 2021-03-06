<?php
namespace GBublik\Lpdt;

use Exception;
use GBublik\Lpdt\Commands\Debug;
use GBublik\Lpdt\Commands\Tcp;
use GBublik\Lpdt\Commands\WebSocket;
use Symfony\Component\Console\Command\Command;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LogServer
{
    /** @var string Application title */
    const APPLICATION_NAME =
'█░░ █▀▄ █▀▄ ▀█▀ 
█░▄ █░█ █░█ ░█░ 
▀▀▀ █▀░ ▀▀░ ░▀░ 
PSR-3 Long process debug tool';

    /** @var string  */
    const APPLICATION_VERSION = '2.0';

    /** @var string Default command.  */
    const DEFAULT_COMMAND = 'debug';

    /** @var Application */
    protected $application;

    /** @var HandlerInterface  */
    protected $handler;

    protected $options;

    /**
     * @param HandlerInterface $handler
     * @param array $options
     * @throws Exception
     */
    public function __construct(HandlerInterface $handler, array $options = [])
    {
        $this->handler = $handler;
        $this->options = $options;

        $this->initApplication();
    }

    /**
     * @throws Exception
     */
    protected function initApplication()
    {

        $this->application = new Application(self::APPLICATION_NAME, self::APPLICATION_VERSION);

        $this->application->addCommands($this->getCommands());

        if (array_key_exists('default-command', $this->options)) {
            $this->application->setDefaultCommand($this->options['default-command']);
        }

        $this->application->run();
    }

    /**
     * @param string|null $commandName
     * @return array|Command
     */
    protected function getCommand(string $commandName = null)
    {
        static $commands = [];
        if (empty($commands)) {
            $commands = [
                'debug' => new Debug($this->handler, $this->options),
                'websocket' => new WebSocket($this->handler, $this->options),
                'tcp' => new Tcp($this->handler, $this->options)
            ];
        }
        return empty($commandName) && !array_key_exists($commandName, $commands) ? $commands : $commands[$commandName];
    }

    protected function getCommands()
    {
        return $this->getCommand();
    }
}