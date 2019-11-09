<?php
namespace GBublik\Lpdt\Writer;

interface WriterInterface
{
    /**
     * @param string|array $str
     */
    public function write($str);

    /**
     * @param string $str
     */
    public function error(string $str);

    /**
     * @param string $step
     */
    public function step(string $step);

    public function finish(string $message = null);
}