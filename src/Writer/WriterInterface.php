<?php
namespace GBublik\Lpdt\Writer;

interface WriterInterface
{
    /**
     * Сообщение с типом "Информация"
     * @param string $message
     */
    public function info(string $message);

    /**
     * Сообщение с типом "Ошибка"
     * @param string $message
     */
    public function error(string $message);

    /**
     * Установка текущего шага
     * @param string $step
     */
    public function step(string $step);

    /**
     * Финальное сообщение
     * @param string $message
     */
    public function finish(string $message = null);
}