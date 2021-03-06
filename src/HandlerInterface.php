<?php
namespace GBublik\Lpdt;

use GBublik\Lpdt\Commands\WriterInterface;

/**
 * Интерфейс обработчика (обработчика)
 * @package GBublik\Lpdt
 */
interface HandlerInterface
{
    /**
     * @param WriterInterface $log
     */
    public function execute(WriterInterface $log);
}