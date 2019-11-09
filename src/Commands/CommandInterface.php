<?php
namespace GBublik\Lpdt\Commands;


use GBublik\Lpdt\HandlerInterface;
use Symfony\Component\Console\Command\Command;

abstract class CommandInterface extends Command
{
    /** @var HandlerInterface  */
    protected $handler;

    public function __construct(HandlerInterface $handler, array $options = [])
    {
        $this->handler = $handler;
        parent::__construct();
    }
}