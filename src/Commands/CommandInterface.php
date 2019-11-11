<?php
namespace GBublik\Lpdt\Commands;


use GBublik\Lpdt\HandlerInterface;
use Symfony\Component\Console\Command\Command;

abstract class CommandInterface extends Command
{
    /** @var HandlerInterface  */
    protected $handler;

    protected $options = [];

    public function __construct(HandlerInterface $handler, array $options = [])
    {
        $this->handler = $handler;
        $this->options = $options;
        parent::__construct();
    }
}