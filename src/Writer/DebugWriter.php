<?php
namespace GBublik\Lpdt\Writer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

/**
 * Пишит данные в консоль
 * @package GBublik\Lpdt\Writer
 */
class DebugWriter implements WriterInterface
{
    /** @var OutputInterface  */
    protected $output;

    /** @var InputInterface  */
    protected $input;

    /** @var ConsoleSectionOutput */
    protected $infoSection;

    /** @var ConsoleSectionOutput */
    protected $errorSection;

    /** @var integer Message stack size  */
    protected $stackInfo;

    /** @var integer Error stack size  */
    protected $stackError;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $this->errorSection = $this->output->section();
        $this->infoSection = $this->output->section();

        $this->stackInfo = $this->input->getOption('stack-info');
        $this->stackError = $this->input->getOption('stack-error');
    }

    /**
     * Сообщение с типом "Ошибка"
     * @param string $message
     */
    public function error(string $message)
    {
        static $logs = [];

        $logs[] = $message;

        if (isset($this->stackInfo)) {
            if (isset($this->stackError) && !$this->stackError) return;
            elseif (isset($this->stackError) && $this->stackError > 0 && count($logs) > $this->stackError) {
                array_shift($logs);
                $this->errorSection->clear();
                foreach ($logs as $log) $this->errorSection->writeLn('<fg=red>Error: ' . $log . '</>');
            } else {
                $this->errorSection->writeln('<fg=red>Error: ' . $message . '</>');
            }
        } elseif (!isset($this->stackError) || (isset($this->stackError) && !empty($this->stackError))) {
            $this->infoSection->writeln('<fg=red>Error: ' . $message . '</>');
        }
    }

    /**
     * @param string $message
     */
    public function info(string $message)
    {
        static $logs = [];

        $logs[] = $message;

        if (isset($this->stackInfo) && !$this->stackInfo)
            return;
        elseif (isset($this->stackInfo) && $this->stackInfo > 0 && count($logs) > $this->stackInfo) {
            array_shift($logs);
            $this->infoSection->clear();
            foreach ($logs as $log) $this->infoSection->writeln($log);
        } else {
            $this->infoSection->writeln($message);
        }
    }

    /**
     * Установка текущего шага
     * @param string $step
     */
    public function step(string $step)
    {
        $this->output->write('<fg=blue>Step: ' . $step . '</>');
    }

    /**
     * Финальное сообщение
     * @param string|null $message
     */
    public function finish(string $message = null)
    {
        if ($this->stackInfo !== null)
            $this->infoSection->clear();
        if (isset($message))
            $this->infoSection->writeLn('<info>' . $message . '</info>');
        else
            $this->infoSection->writeLn('<info>Lpdt finished its work</info>');
    }
}