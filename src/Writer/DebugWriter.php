<?php
namespace GBublik\Lpdt\Writer;

declare(ticks=3000);

use DateTime;
use Exception;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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
    protected $greetingSection;

    /** @var ConsoleSectionOutput */
    protected $tableHeadSection;

    /** @var ConsoleSectionOutput */
    protected $statisticSection;

    /** @var ConsoleSectionOutput */
    protected $progressSection;

    /** @var ConsoleSectionOutput */
    protected $errorSection;

    /** @var integer Message stack size  */
    protected $stackMessage;

    /** @var integer Error stack size  */
    protected $stackError;

    /** @var Logger  */
    protected $monolog;

    /** @var bool Если включено файловое логирование */
    protected $monologMode = false;

    /** @var string */
    protected $currentStep;

    /** @var DateTime время запуска */
    protected $timeStart;

    protected $helpers = [];

    /** @var  ProgressBar*/
    protected $progressBar;

    protected $quiet = false;

    /**
     * DebugWriter constructor.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    public function __construct(InputInterface &$input, OutputInterface &$output)
    {
        $this->output = $output;
        $this->input = $input;

        $this->greetingSection = $this->output->section();
        $this->errorSection = $this->output->section();
        $this->infoSection = $this->output->section();
        $this->tableHeadSection = $this->output->section();
        $this->progressSection = $this->output->section();
        $this->statisticSection = $this->output->section();

        $this->stackMessage = $this->input->getOption('stack-message');
        $this->stackError = $this->input->getOption('stack-error');
        $this->quiet = $this->input->getOption('quiet');


        $this->initMonolog(); // Инициализация файлового логера Monolog

        if (!$this->quiet) {
            $this->timeStart = new DateTime;

            $this->helpers = [
                'formatter' => new FormatterHelper,
                'question' => new QuestionHelper
            ];
            $this->setStyles(); // Установка стилей
            $this->setProgressPar(); // Установка прогресс бара
            $this->greetingSection->writeln('<greeting>Отладочный режим</greeting>');
            $this->greetingSection->writeln("============================================================================");
        }
    }

    /**
     * Сообщение с типом "Ошибка"
     * @param string $message
     */
    public function error(string $message)
    {
        if ($this->monologMode) {
            $this->monolog->error($message, $this->getContextLog());
        }
        if ($this->quiet) return;

        static $logs = [];

        $logs[] = $message;
        $message = $this->errorBlock($message);
        $question = new ConfirmationQuestion($message . ' Продолжить [y/n]?', true,'/^(y)/i');

        if (!$this->helpers['question']->ask($this->input, $this->statisticSection, $question)) {
            $this->finish('The script exited on an error');
            die();
        }

        if (isset($this->stackMessage)) {
            if (isset($this->stackError) && !$this->stackError) return;
            elseif (isset($this->stackError) && $this->stackError > 0 && count($logs) > $this->stackError) {
                array_shift($logs);
                $this->errorSection->clear();
                foreach ($logs as $log) $this->errorSection->write($this->errorBlock($log));
            } else {
                $this->errorSection->write($message);
            }
        } elseif (!isset($this->stackError) || (isset($this->stackError) && !empty($this->stackError))) {
            $this->infoSection->write($message);
        }
        $this->statistic('error');
    }

    /**
     * @param string|array $message
     */
    public function info($message)
    {
        if ($this->monologMode) $this->monolog->info($message, $this->getContextLog());

        if ($this->quiet) return;

        static $logs = [];

        $logs[] = $message;

        if (isset($this->stackMessage) && !$this->stackMessage)
            return;
        elseif (isset($this->stackMessage) && $this->stackMessage > 0 && count($logs) > $this->stackMessage) {
            array_shift($logs);
            $this->infoSection->clear();
            foreach ($logs as $log) $this->infoBlock($log);
        } else {
            $this->infoBlock($message);
        }
        $this->statistic('info');
    }

    /**
     * Установка текущего шага
     * @param string $step
     */
    public function step(string $step)
    {
        if ($this->quiet) return;

        $this->currentStep = $step;
        if ($this->monologMode) $this->monolog->info('Этап: ' . $this->currentStep);
    }

    /**
     * Финальное сообщение
     * @param string|null $message
     */
    public function finish(string $message = null)
    {
        if ($this->quiet) return;

       if ($this->stackMessage !== null) {
            $this->infoSection->clear();
        }
        if (isset($message)) {
            $this->infoSection->writeLn('<finish>' . $message . '</finish>');
            if ($this->monologMode) $this->monolog->info($message);
        } else {
            $this->infoSection->writeLn('<finish>The script has completed work</finish>');
            if ($this->monologMode) $this->monolog->info('The script has completed work');
        }


        $this->statistic('finish');
        $this->progressSection->clear();
    }

    /**
     * @throws Exception
     */
    protected function initMonolog()
    {
        $this->monolog = new Logger('LPDT');

        $file = $this->input->getOption('log-file');
        $level = $this->input->getOption('log-level');
        $overwrite = $this->input->getOption('log-overwrite');
        $daysSave = $this->input->getOption('log-days');

        switch (strtoupper($level)) {
            case 'ERROR': $level = Logger::ERROR; break;
            default: $level = Logger::DEBUG;
        }

        if (!empty($file)) {
            $this->monologMode = true;
            if (file_exists($file) && !isset($overwrite)) {
                unlink($file);
            } else if (isset($overwrite)) {
                $baseFilename = basename($file);
                preg_match('/^(.*)?(\..*?$)/', $baseFilename, $matches);
                $path = str_replace($file, '', $file);

                if ($daysSave > 0) {
                    $daysSave--;
                    $logFiles = glob($path . $matches[1] . '*', GLOB_MARK);
                    $logFiles = array_diff($logFiles, [$baseFilename, '']);
                    $logFiles = array_slice($logFiles, 0, $daysSave * -1);
                    foreach ($logFiles as $f) unlink($path . $f);
                }
                $file = $path . $matches[1] . date('Ymd') . $matches[2];
            }
            $this->monolog->pushHandler(new StreamHandler($file, $level));
        }
    }

    protected function getContextLog()
    {
        return [
            'step' => $this->currentStep
        ];
    }

    protected function setProgressPar()
    {
        $this->progressBar = new ProgressBar($this->progressSection);
        $this->progressBar->setFormat('%bar% %message% %bar%');
        $this->progressBar->setProgressCharacter(' ');
        $this->progressBar->setEmptyBarCharacter('=');
        $this->progressBar->start();
    }

    protected function setStyles()
    {
        //Заголовок для ошибки
        $outputStyle = new OutputFormatterStyle('black', 'red');
        $this->output->getFormatter()->setStyle('error-label', $outputStyle);

        $outputStyle = new OutputFormatterStyle('red', null);
        $this->output->getFormatter()->setStyle('error-context', $outputStyle);

        //finish
        $outputStyle = new OutputFormatterStyle('magenta', null, ['bold']);
        $this->output->getFormatter()->setStyle('finish', $outputStyle);

        //Приветствие
        $outputStyle = new OutputFormatterStyle('magenta', null, ['bold', 'blink']);
        $this->output->getFormatter()->setStyle('greeting', $outputStyle);
    }

    protected function statistic($type = 'info')
    {
        static $info = 0;
        static $error = 0;
        static $limitMemory;

        if (empty($limitMemory)) $limitMemory = ini_get('memory_limit');

        switch ($type) {
            case 'finish':
            case 'refresh':
                break;
            case 'error': $error++; break;
            default: $info++; break;
        }

        $currentMemory = memory_get_peak_usage();
        $cpu = array_reduce(sys_getloadavg(), function ($value, $item){
            return ($value + $item) / 2;
        });

        $message = sprintf(
            '<info>Messages: </info>%s; <info>Errors:</info> %s; <info>Mem:</info> %s/%s; <info>Sys-load:</info> %s <info>Time:</info> %s',
            $info,
            $error,
            $this->convertUnitByte($currentMemory),
            $limitMemory >= 0 ? $this->convertUnitByte($limitMemory) : '~',
            $cpu ?: '~',
            $this->timeStart->diff(new DateTime)->format('%H:%I:%S')
        );

        $this->progressBar->setMessage($this->helpers['formatter']->truncate($this->currentStep, 25) ?? false);
        $this->progressBar->advance();

        $this->statisticSection->clear();
        $this->statisticSection->write($message);
    }

    /**
     * @param string|array $message
     */
    protected function infoBlock($message)
    {
        static $tableHeader;
        static $maxWidth = 25;
        //$message = $message['file_name'];
        if (is_array($message)) {
            $message = array_slice($message, 0, 5);
            if (empty($tableHeader)) $tableHeader = new Table($this->tableHeadSection);
            $tableRow = new Table($this->infoSection);
            $tableRow->setStyle('compact');
            $message = array_map(function ($value) use ($maxWidth) {
                return  '   ' . $this->helpers['formatter']->truncate($value, $maxWidth - 10);
            }, $message);

            $c = count($message);
            for($i = 0; $i <= $c; $i++) $tableHeader->setColumnWidth($i, $maxWidth);
            $tableHeader->setHeaders(array_keys($message));

            $this->tableHeadSection->clear();
            $tableHeader->render();

            for($i = 0; $i <= $c; $i++) $tableRow->setColumnMaxWidth($i, $maxWidth + 1);
            for($i = 0; $i <= $c; $i++) $tableRow->setColumnWidth($i, $maxWidth + 1);
            $tableRow->addRow($message);
            $tableRow->render();
        } else {
            $this->tableHeadSection->clear();

            if (empty($this->currentStep)) {
                $this->infoSection->write($message);
            } else {
                $this->infoSection->write($this->helpers['formatter']->formatSection(
                    $this->currentStep,
                    $message
                ));
            }
        }
    }

    protected function errorBlock(string $message)
    {
        return sprintf('<error-label>Err: %s</error-label> <error-context>%s </error-context> ', $this->currentStep, $message);
    }

    protected function convertUnitByte($num)
    {
        $neg = $num < 0;
        $units = array('b', 'kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb', 'Zb', 'Yb');
        if ($neg) {
            $num = -$num;
        }
        if ($num < 1) {
            return ($neg ? '-' : '') . $num . ' B';
        }
        $exponent = min(floor(log($num) / log(1000)), count($units) - 1);
        $num = sprintf('%.02F', ($num / pow(1000, $exponent)));
        $unit = $units[$exponent];
        return ($neg ? '-' : '') . $num . $unit;
    }
}