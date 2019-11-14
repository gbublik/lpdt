<?php
namespace GBublik\Lpdt\Commands;

use GBublik\Lpdt\Socket\TcpServer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TcpWriter implements WriterInterface
{
    protected $server;

    protected $serverInput;

    protected $serverOutput;

    protected $options = [];

    public function __construct(InputInterface &$serverInput, OutputInterface &$serverOutput)
    {
        $this->serverInput = &$serverInput;
        $this->serverOutput = &$serverOutput;
        $this->options = $this->serverInput->getOptions();

        $this->server  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($this->server , '0.0.0.0', 8090);
    }

    /**
     * Отладочная информация, Например: сделали запрос на удаленный сервер, нормализовали данные
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context = [])
    {
        socket_write($this->server, $message);
    }

    /**
     * Информативное сообщение. Например: Добавлен новый пользователь lpdt_user(1)
     * @param string $message
     * @param array $context
     */
    public function info(string $message, array $context = [])
    {
        // TODO: Implement info() method.
    }

    /**
     * Уведомление. Например: Пользователь lpdt_user(1) добавлен, но у него не указан телефон
     * @param string $message
     * @param array $context
     */
    public function notice(string $message, array $context = [])
    {
        // TODO: Implement notice() method.
    }

    /**
     *  Предупреждение. Например: Пользователь lpdt_user(1) не имеет пароля
     * @param string $message
     * @param array $context
     */
    public function warning(string $message, array $context = [])
    {
        // TODO: Implement warning() method.
    }

    /**
     * Ошибка. Например: Пользователь lpdt_user(1) уже зарегистрирован, но это не критично, пропускаем
     * @param string $message
     * @param array $context
     */
    public function error(string $message, array $context = [])
    {
        // TODO: Implement error() method.
    }

    /**
     * Критическая ошибка.
     * Например: Пользователь (1) нет логина. Ошибка критичная, нужно подумать продолжить процесс или нет.
     * Будет предложен выбор, продолжить работу скрипта или остановить. В случаи если вывод данных в консоль отключен (cron)
     * выбор будет по умолчанию - продолжить выполнение скрипта
     * @param string $message
     * @param array $context
     */
    public function critical(string $message, array $context = [])
    {
        // TODO: Implement critical() method.
    }

    /**
     * Тревога. Например: Тоже самое что и critical, но еще нужно уведомить разработчика по email
     * @param string $message
     * @param array $context
     */
    public function alert(string $message, array $context = [])
    {
        // TODO: Implement alert() method.
    }

    /**
     * Авария. Тоже самое что и alert, но обработчик дальше выполняться не может,
     * например критическая ошибка в коде
     * @param string $message
     * @param array $context
     */
    public function emergency(string $message, array $context = [])
    {
        // TODO: Implement emergency() method.
    }

    /**
     * Установка текущего этапа выполнения обработчика
     * @param string $step
     */
    public function step(string $step)
    {
        // TODO: Implement step() method.
    }

    /**
     * Финальное сообщение
     * @param string $message
     */
    public function finish(string $message = null)
    {
        fclose($this->server);
    }
}