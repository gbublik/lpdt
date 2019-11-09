<?php
namespace GBublik\Lpdt;

use Exception;
use GBublik\Lpdt\Commands\Debug;
use GBublik\Lpdt\Application;
use Symfony\Component\Console\Command\Command;

class LogServer
{
    /** @var string Application title */
    const APPLICATION_NAME =
'▒██░░░▒▐█▀▀█▌▒██▄░▒█▌░▐█▀▀▀─     ▒▐█▀█▒▐█▀▀▄▒▐█▀▀█▌░▐█▀█░▐█▀▀▒▄█▀▀█▒▄█▀▀█     ░▐█▀█▄░▐█▀▀░▐█▀▄─▒█▒█░▐█▀▀▀─     ▒█▀█▀█▒▐█▀▀█▌▒▐█▀▀█▌▒██░░░
▒██░░░▒▐█▄▒█▌▒▐█▒█▒█░░▐█░▀█▌     ▒▐█▄█▒▐█▒▐█▒▐█▄▒█▌░▐█──░▐█▀▀▒▀▀█▄▄▒▀▀█▄▄     ░▐█▌▐█░▐█▀▀░▐█▀▀▄▒█▒█░▐█░▀█▌     ░░▒█░░▒▐█▄▒█▌▒▐█▄▒█▌▒██░░░
▒██▄▄█▒▐██▄█▌▒██░▒██▌░▐██▄█▌     ▒▐█░░▒▐█▀▄▄▒▐██▄█▌░▐█▄█░▐█▄▄▒█▄▄█▀▒█▄▄█▀     ░▐█▄█▀░▐█▄▄░▐█▄▄▀▒▀▄▀░▐██▄█▌     ░▒▄█▄░▒▐██▄█▌▒▐██▄█▌▒██▄▄█';

    /** @var string  */
    const APPLICATION_VERSION = '2.0';

    /** @var string Default command.  */
    const DEFAULT_COMMAND = 'debug';

    /** @var Application */
    protected $application;

    /** @var HandlerInterface  */
    protected $handler;

    /**
     * @param HandlerInterface $handler
     * @throws Exception
     */
    public function __construct(HandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->initApplication();
    }

    /**
     * @throws Exception
     */
    protected function initApplication()
    {

        $this->application = new Application(self::APPLICATION_NAME, self::APPLICATION_VERSION);

        $this->application->addCommands($this->getCommands());

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
                'debug' => new Debug($this->handler),
                //'websocket' => new WebSocket($this->callback)
            ];
        }
        return empty($commandName) && !array_key_exists($commandName, $commands) ? $commands : $commands[$commandName];
    }

    protected function getCommands()
    {
        return $this->getCommand();
    }
}