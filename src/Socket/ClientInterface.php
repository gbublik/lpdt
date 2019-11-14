<?php
namespace GBublik\Lpdt\Socket;

/**
 * @package GBublik\Lpdt\Socket
 */
interface ClientInterface
{
    /**
     * Отправляет сообщение клиенту
     * @param string $message
     * @return mixed
     */
    public function send(string $message);

    /**
     * @return string|null
     */
    public function read();

    public function disconnect();
}