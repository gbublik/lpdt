<?php
namespace GBublik\Lpdt\Writer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

class DebugWriter implements WriterInterface
{
    protected $output;

    protected $input;

    /** @var ConsoleSectionOutput */
    protected $logSection;

    /** @var ConsoleSectionOutput */
    protected $errorSection;

    /** @var integer Message stack size  */
    protected $stackWrite;

    /** @var integer Error stack size  */
    protected $stackError;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->errorSection = $this->output->section();
        $this->logSection = $this->output->section();

        $this->stackWrite = $this->input->getOption('stack-write');
        $this->stackError = $this->input->getOption('stack-error');
    }

    /**
     * @param string|array $str
     */
    public function write($str)
    {
        static $logs = [];

        $logs[] = $str;

        if (isset($this->stackWrite) && !$this->stackWrite)
            return;
        elseif (isset($this->stackWrite) && $this->stackWrite > 0 && count($logs) > $this->stackWrite) {
            array_shift($logs);
            $this->logSection->clear();
            foreach ($logs as $log) $this->logSection->writeln($log);
        } else {
            $this->logSection->writeln($str);
        }

    }

    /**
     * @param string $str
     */
    public function error(string $str)
    {
        static $logs = [];

        $logs[] = $str;

        if (isset($this->stackWrite)) {
            if (isset($this->stackError) && !$this->stackError) return;
            elseif (isset($this->stackError) && $this->stackError > 0 && count($logs) > $this->stackError) {
                array_shift($logs);
                $this->errorSection->clear();
                foreach ($logs as $log) $this->errorSection->writeLn('<fg=red>Error: ' . $log . '</>');
            } else {
                $this->errorSection->writeln('<fg=red>Error: ' . $str . '</>');
            }
        } elseif (!isset($this->stackError) || (isset($this->stackError) && !empty($this->stackError))) {
            $this->logSection->writeln('<fg=red>Error: ' . $str . '</>');
        }
    }

    /**
     * @param string $step
     */
    public function step(string $step)
    {
        $this->output->write('<fg=blue>Step: ' . $step . '</>');
    }

    public function finish(string $message = null)
    {
        if ($this->stackWrite !== null)
            $this->logSection->clear();
        if (isset($message))
            $this->logSection->writeLn('<fg=black;bg=cyan>' . $message . '</>');
        else
            $this->logSection->writeLn('<fg=black;bg=cyan>Lpdt finished its work</>');
    }
}