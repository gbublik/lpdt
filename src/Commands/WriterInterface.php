<?php
namespace GBublik\Lpdt\Commands;

/**
 * Интерфейс "писца" - Объект пишущий в лог
 * PSR-3 methods - debug, info, notice, warning, error, critical, alert, emergency
 * @package GBublik\Lpdt\Writer
 */
interface WriterInterface
{
    /**
     * Отладочная информация, Например: сделали запрос на удаленный сервер, нормализовали данные
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context = []);

    /**
     * Информативное сообщение. Например: Добавлен новый пользователь lpdt_user(1)
     * @param string $message
     * @param array $context
     */
    public function info(string $message, array $context = []);

    /**
     * Уведомление. Например: Пользователь lpdt_user(1) добавлен, но у него не указан телефон
     * @param string $message
     * @param array $context
     */
    public function notice(string $message, array $context = []);

    /**
     *  Предупреждение. Например: Пользователь lpdt_user(1) не имеет пароля
     * @param string $message
     * @param array $context
     */
    public function warning(string $message, array $context = []);

    /**
     * Ошибка. Например: Пользователь lpdt_user(1) уже зарегистрирован, но это не критично, пропускаем
     * @param string $message
     * @param array $context
     */
    public function error(string $message, array $context = []);

    /**
     * Критическая ошибка.
     * Например: Пользователь (1) нет логина. Ошибка критичная, нужно подумать продолжить процесс или нет.
     * Будет предложен выбор, продолжить работу скрипта или остановить. В случаи если вывод данных в консоль отключен (cron)
     * выбор будет по умолчанию - продолжить выполнение скрипта
     * @param string $message
     * @param array $context
     */
    public function critical(string $message, array $context = []);

    /**
     * Тревога. Например: Тоже самое что и critical, но еще нужно уведомить разработчика по email
     * @param string $message
     * @param array $context
     */
    public function alert(string $message, array $context = []);

    /**
     * Авария. Тоже самое что и alert, но обработчик дальше выполняться не может,
     * например критическая ошибка в коде
     * @param string $message
     * @param array $context
     */
    public function emergency(string $message, array $context = []);

    /**
     * Установка текущего этапа выполнения обработчика
     * @param string $step
     */
    public function step(string $step);

    /**
     * Финальное сообщение
     * @param string $message
     */
    public function finish(string $message = null);
}