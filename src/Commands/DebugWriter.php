<?php
namespace GBublik\Lpdt\Commands;


use DateTime;
use Exception;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Пишит данные в консоль
 * @package GBublik\Lpdt\Writer
 */
class DebugWriter implements WriterInterface
{
    protected $options = [];

    /** @var OutputInterface  */
    protected $output;

    /** @var InputInterface  */
    protected $input;

    /** @var  ConsoleSectionOutput[]*/
    protected $appSections = [
        'greeting', // Greeting on start handler
        'error', // Сообщения выше или равно NOTICE
        'messages', // Сообщения ниже NOTICE
        'tableHead', // Секция для зашаловков таблиц
        'progress', // Progress bar
        'statistic', // Общая статистика
    ];

    /** @var Logger  */
    protected $monolog;

    /** @var string Текущий этап выполнения обработчика */
    protected $currentStep;

    /** @var DateTime время запуска обработчика */
    protected $timeStart;

    /** @var Helper[] Вспомогательные библиотеки для вывода данных в консоль */
    protected $helpers = [];

    /** @var  ProgressBar */
    protected $progressBar;

    /** @var QueueMessages[] Очереди областей вывода в консоль */
    protected $queues = [
        'messages', // Элементы информативного типа
        'errors' // Элементы ошибочного типа
    ];

    protected $accessLevel = [
        'DEBUG' => Logger::DEBUG,
        'INFO' => Logger::INFO,
        'NOTICE' => Logger::NOTICE,
        'WARNING' => Logger::WARNING,
        'ERROR' => Logger::ERROR,
        'CRITICAL' => Logger::CRITICAL,
        'ALERT' => Logger::ALERT,
        null => Logger::DEBUG
    ];

    protected $access;

    /** @var bool Флаг включенности файлового логирование */
    protected $flagMonolog = false;

    /**
     * Флаг режима вывода сообщений.
     * Если true выводит все сообщения "портянкой"
     * @var bool
     */
    protected $flagVerbose = false;

    /** @var bool Флаг вывода в консоль */
    protected $flagQuiet = false;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    public function __construct(InputInterface &$input, OutputInterface &$output)
    {
        $this->input = &$input;

        $this->options = $this->input->getOptions();
        $this->access = $this->accessLevel[strtoupper($this->options['level'])];
        $this->initMonolog(); // Инициализация файлового логера Monolog

        $this->flagQuiet = array_key_exists('quiet', $this->options) && is_null($this->options['quiet']);

        // Вывод в консоль.
        if (!$this->flagQuiet) {
            $this->output = &$output;
            $this->appSections = [
                'greeting' => $this->output->section(),
                'error' => $this->output->section(),
                'messages' => $this->output->section(),
                'tableHead' => $this->output->section(),
                'progress' => $this->output->section(),
                'statistic' => $this->output->section()
            ];

            $this->timeStart = new DateTime;

            $this->helpers = [
                'formatter' => new FormatterHelper,
                'question' => new QuestionHelper
            ];
            $this->setStyles(); // Установка стилей
            $this->setProgressPar(); // Установка прогресс бара
            $this->appSections['greeting']->writeln('<greeting>Отладочный режим</greeting>');
            $this->appSections['greeting']->writeln("============================================================================");

            if (is_null($this->options['queues-messages'])) {
                $this->flagVerbose = true;
            }

            if (!$this->flagVerbose) {
                $paramQueueErrors = $this->options['queues-errors'];
                $this->queues = [
                    'messages' => new QueueMessages($this->input->getOption('queues-messages')),
                    'errors' => $paramQueueErrors ? new QueueMessages($paramQueueErrors) : $paramQueueErrors
                ];
            }
        }
    }

    /**
     * Отладочная информация. Например: сделали запрос на удаленный сервер, вывели массив c данными
     * @param mixed $message
     * @param array $context
     * @throws Exception
     */
    public function debug(string $message, array $context = [])
    {
        if ($this->flagMonolog)
            $this->monolog->debug(($this->currentStep ? '[' . $this->currentStep . '] ' : '') . $message, $context);

        if ($this->flagQuiet) return; // Если тихий режим дальше не идем

        if ($this->access == Logger::DEBUG) $this->infoMessage($message, $context, 'debug');
        $this->statistic('debug');
    }

    /**
     * Информативное сообщение. Например: Добавлен новый пользователь lpdt_user(1)
     * @param mixed $message
     * @param array $context
     * @param string $prefix
     * @throws Exception
     */
    public function info(string $message, array $context = [], $prefix = 'info')
    {
        if ($prefix == 'info' && $this->flagMonolog)
            $this->monolog->info(($this->currentStep ? '[' . $this->currentStep . '] ' : '') . $message, $context);

        if ($this->flagQuiet) return; // Если тихий режим дальше не идем

        if ($prefix == 'info') $this->statistic('info');

        if ($this->access > Logger::INFO) return;

        $this->infoMessage($message, $context, $prefix);
    }

    /**
     * Уведомление. Например: Пользователь lpdt_user(1) добавлен, но у него не указан телефон
     * @param string $message
     * @param array $context
     * @throws Exception
     */
    public function notice(string $message, array $context = [])
    {
        if ($this->flagMonolog)
            $this->monolog->notice(($this->currentStep ? '[' . $this->currentStep . '] ' : '') . $message, $context);

        if ($this->flagQuiet) return; // Если тихий режим дальше не идем

        if ($this->access <= Logger::NOTICE) $this->infoMessage($message, $context, 'notice');
        $this->statistic('notice');
    }

    /**
     *  Предупреждение. Например: Пользователь lpdt_user(1) не имеет пароля
     * @param mixed $message
     * @param array $context
     */
    public function warning(string $message, array $context = [])
    {
        if ($this->flagMonolog) {
            $this->monolog->warning(($this->currentStep ? '[' . $this->currentStep . ']' : null) . $message, $context);
        }

        if ($this->flagQuiet) return; //Если тихий ход
        if ($this->access <= Logger::WARNING) $this->errorMessage($message, $context, 'warning');

        $this->statistic('warning');
    }

    /**
     * Ошибка
     * @param mixed $message
     * @param array $context
     */
    public function error(string $message, array $context = [])
    {
        if ($this->flagMonolog) {
            $this->monolog->error(($this->currentStep ? '[' . $this->currentStep . '] ' : null) . $message, $context);
        }

        if ($this->flagQuiet) return; //Если тихий ход

        if ($this->access <= Logger::ERROR) $this->errorMessage($message, $context, 'error');

        $this->statistic('error');
    }

    /**
     * Критическая ошибка.
     * Например: Пользователь (1) нет логина. Ошибка критичная, нужно подумать продолжить процесс или нет.
     * Будет предложен выбор
     * @param mixed $message
     * @param array $context
     */
    public function critical(string $message, array $context = [])
    {
        if ($this->flagMonolog) {
            $this->monolog->critical(($this->currentStep ? '[' . $this->currentStep . '] ' : null) . $message, $context);
        }

        if ($this->flagQuiet) return; //Если тихий ход

        $this->statistic('critical');

        if ($this->access <= Logger::CRITICAL) {
            $this->errorMessage($message, $context, 'critical');
        }
        if ($this->access > Logger::CRITICAL) return;

        $helper = $this->helpers['question'];
        $this->appSections['statistic']->writeln(
            sprintf(
                '<critical>%s</critical>',
                $message . (!empty($context) ? ' ' . json_encode($context) : '')
            )
        );
        $question = new ConfirmationQuestion('Критическая ошибка. Продолжить выполнение скрипта [yes/no] default: yes', true);

        if (!$helper->ask($this->input, $this->appSections['statistic'], $question)) {
            $this->finish('Скрипт завершил свою работу на критической ошибке');
            die();
        }
    }

    /**
     * Тревога. Например: Тоже самое что и "Пользователь (1) нет логина", но нужно еще уведомить разработчика
     * @param mixed $message
     * @param array $context
     */
    public function alert(string $message, array $context = [])
    {
        if ($this->flagMonolog) {
            $this->monolog->alert(($this->currentStep ? '[' . $this->currentStep . '] ' : null) . $message, $context);
        }

        if ($this->flagQuiet) return; //Если тихий ход

        $this->statistic('alert');

        if ($this->access <= Logger::ALERT) $this->errorMessage($message, $context, 'alert');

        if ($this->access > Logger::ALERT) return;

        $helper = $this->helpers['question'];
        $this->appSections['statistic']->writeln(
            sprintf(
                '<critical>%s</critical>',
                $message . (!empty($context) ? ' ' . json_encode($context) : '')
            )
        );
        $question = new ConfirmationQuestion('Критическая ошибка. Продолжить выполнение скрипта [yes/no] default: yes', true);

        if (!$helper->ask($this->input, $this->appSections['statistic'], $question)) {
            $this->finish('Скрипт завершил свою работу на критической ошибке');
            die();
        }
    }

    /**
     * Авария. Тоже самое что и алерт, но обработчик дальше выполняться не может,
     * например критическая ошибка в коде
     * @param mixed $message
     * @param array $context
     */
    public function emergency(string $message, array $context = [])
    {
        if ($this->flagMonolog) {
            $this->monolog->emergency(($this->currentStep ? '[' . $this->currentStep . '] ' : null) . $message, $context);
        }
        die();
    }

    /**
     * Текущего шага
     * @param string $step
     * @throws Exception
     */
    public function step(string $step)
    {
        if ($this->flagQuiet) return; // Если тихий режим дальше не идем
        $this->currentStep = $step;
        $this->appSections['messages']->writeln(sprintf('<step>Step: %s</step>', $step));
    }

    /**
     * Финальное сообщение
     * @param string|null $message
     */
    public function finish(string $message = null)
    {
        if ($this->flagQuiet) return;

        if (!is_null($this->options['queues-messages'])) {
            $this->appSections['messages']->clear();
        }
        if (isset($message)) {
            $this->appSections['messages']->writeLn('<finish>' . $message . '</finish>');
            if ($this->flagMonolog) $this->monolog->info($message);
        } else {
            $this->appSections['messages']->writeLn('<finish>The script has completed work</finish>');
            if ($this->flagMonolog) $this->monolog->info('The script has completed work');
        }

        $this->statistic('finish');
        $this->appSections['progress']->clear();
    }

    /**
     * @throws Exception
     */
    protected function initMonolog()
    {
        $this->monolog = new Logger('LPDT');

        $file = $this->options['log-file'];
        $level = $this->options['level'];
        $overwrite = $this->options['log-overwrite'];
        $daysSave = $this->options['log-days'];

        switch (strtoupper($level)) {
            case 'DEBUG': $level = Logger::DEBUG; break;
            case 'INFO': $level = Logger::INFO; break;
            case 'NOTICE': $level = Logger::NOTICE; break;
            case 'ERROR': $level = Logger::ERROR; break;
            case 'ALERT': $level = Logger::ALERT; break;
            case 'CRITICAL': $level = Logger::CRITICAL; break;
            default: $level = Logger::DEBUG;
        }

        if (!empty($file)) {
            $this->flagMonolog = true;
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

    protected function setProgressPar()
    {
        if (is_null($this->options['light'])) return;
        $this->progressBar = new ProgressBar($this->appSections['progress']);
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

        //green
        $outputStyle = new OutputFormatterStyle('green', null);
        $this->output->getFormatter()->setStyle('green', $outputStyle);

        //step
        $outputStyle = new OutputFormatterStyle('black', 'green');
        $this->output->getFormatter()->setStyle('step', $outputStyle);

        //DEBUG
        $outputStyle = new OutputFormatterStyle('white', null);
        $this->output->getFormatter()->setStyle('debug', $outputStyle);

        //INFO
        $outputStyle = new OutputFormatterStyle('default', null);
        $this->output->getFormatter()->setStyle('info', $outputStyle);

        //NOTICE
        $outputStyle = new OutputFormatterStyle('blue', null);
        $this->output->getFormatter()->setStyle('notice', $outputStyle);

        //WARNING
        $outputStyle = new OutputFormatterStyle('yellow', null);
        $this->output->getFormatter()->setStyle('warning', $outputStyle);

        //ERROR
        $outputStyle = new OutputFormatterStyle('red', null);
        $this->output->getFormatter()->setStyle('error', $outputStyle);

        //CRITICAL
        $outputStyle = new OutputFormatterStyle('default', 'red');
        $this->output->getFormatter()->setStyle('critical', $outputStyle);

        //ALERT
        $outputStyle = new OutputFormatterStyle('default', 'red', ['bold', 'blink']);
        $this->output->getFormatter()->setStyle('alert', $outputStyle);
    }

    protected function statistic($type = 'info')
    {
        if (is_null($this->options['light'])) return;
        static $info = 0;
        static $error = 0;
        static $limitMemory;

        if (empty($limitMemory)) $limitMemory = ini_get('memory_limit');

        switch ($type) {
            case 'finish':
            case 'refresh':
                break;
            case 'warning':
            case 'alert':
            case 'critical':
            case 'error': $error++; break;
            default: $info++; break;
        }

        $currentMemory = memory_get_peak_usage();
        $cpu = array_reduce(sys_getloadavg(), function ($value, $item){
            return ($value + $item) / 2;
        });

        $message = sprintf(
            '<green>Messages: </green>%s; <green>Errors:</green> %s; <green>Mem:</green> %s/%s; <green>Sys-load:</green> %s <green>Time:</green> %s',
            $info,
            $error,
            $this->convertUnitByte($currentMemory),
            $limitMemory >= 0 ? $this->convertUnitByte($limitMemory) : '~',
            $cpu ?: '~',
            $this->timeStart->diff(new DateTime)->format('%H:%I:%S')
        );

        if (!is_null($this->options['light'])) {
            $this->progressBar->setMessage($this->helpers['formatter']->truncate($this->currentStep, 25) ?? false);
            $this->progressBar->advance();
        }


        $this->appSections['statistic']->clear();
        $this->appSections['statistic']->write($message);
    }

    /**
     * @param string $message
     * @param array $context
     * @param string $prefix
     * @throws Exception
     */
    protected function infoMessage(string $message, array $context, string $prefix)
    {
        $message = strtoupper($prefix) . ": " . $message;
        if (isset($this->options['queues-messages']) && empty($this->options['queues-messages'])) return;

        if ($this->flagVerbose) {
            $this->printInfoBlock($message, $context, $prefix);
        } else {
            $this->queues['messages'][] = [$prefix, $message, $context];
            $this->appSections['messages']->clear();
            foreach ($this->queues['messages'] as $qMessage) {
                $this->printInfoBlock($qMessage[1], $qMessage[2], $qMessage[0]);
            }
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @param string $style
     * @throws Exception
     */
    protected function printInfoBlock(string $message, array $context = [], string $style = null)
    {
        static $tableHeader;

        $maxWidth = 25; // Максимальная ширина таблицы

        $paramQueuesMessages = $this->options['queues-messages'];
        if(is_integer($paramQueuesMessages) && empty($paramQueuesMessages)) return;

        if ($style) $message = sprintf('<%s>%s</%s>', $style, $message, $style);

        if (empty($context) || is_null($this->options['light'])) {
            $this->appSections['tableHead']->clear();

            if (empty($this->currentStep)) {
                $this->appSections['messages']->writeln($message);
            } else {
                $this->appSections['messages']->writeln($this->helpers['formatter']->formatSection(
                    sprintf('<%s>%s</%s>', $style, $this->currentStep, $style),
                    $message
                ));
            }
        } else {
            $context = array_slice($context, 0, 5);
            if (empty($tableHeader)) $tableHeader = new Table($this->appSections['tableHead']);
            $tableRow = new Table($this->appSections['messages']);
            $tableRow->setStyle('compact');
            $context = array_map(function ($value) use ($maxWidth) {
                return  '   ' . $this->helpers['formatter']->truncate($value, $maxWidth - 10);
            }, $context);

            $c = count($context);
            for($i = 0; $i <= $c; $i++) $tableHeader->setColumnWidth($i, $maxWidth);
            $tableHeader->setHeaders(array_keys($context));

            $this->appSections['tableHead']->clear();
            $tableHeader->render();
            $this->appSections['tableHead']->writeln($message);

            for($i = 0; $i <= $c; $i++) $tableRow->setColumnMaxWidth($i, $maxWidth + 1);
            for($i = 0; $i <= $c; $i++) $tableRow->setColumnWidth($i, $maxWidth + 1);
            $tableRow->addRow($context);
            $tableRow->render();
        }
    }

    protected function errorMessage(string $message, array $context, string $prefix)
    {
        $message = strtoupper($prefix) . ": " . $message;
        $message = sprintf('<%s>%s</%s>', $prefix, $message . (!empty($context) ? ' ' . json_encode($context) : null), $prefix);
        if ($this->flagVerbose) {
            if (isset($this->currentStep)) {
                $this->appSections['messages']->writeln($this->helpers['formatter']->formatSection(
                    sprintf('<%s>%s</%s>', $prefix, $this->currentStep, $prefix),
                    $message
                ));
            } else {
                $this->appSections['messages']->writeln($message);
            }
        } else {
            $this->queues['errors'][] = [$prefix, $message, $context];

            if ($this->options['queues-errors'] > 0) {
                $this->appSections['error']->clear();
                foreach ($this->queues['errors'] as $queue) {
                    $mess = sprintf('<%s>%s</%s>', $queue[0], $queue[1] . (!empty($queue[2]) ? ' ' . json_encode($queue[2]) : null), $queue[0]);
                    if (isset($this->currentStep)) {
                        $this->appSections['error']->writeln($this->helpers['formatter']->formatSection(
                            sprintf('<%s>%s</%s>', $queue[0], $this->currentStep, $queue[0]),
                            $mess
                        ));
                    } else {
                        $this->appSections['error']->writeln($mess);
                    }
                }
            } else if (is_null($this->options['queues-errors'])) {
                if (isset($this->currentStep)) {
                    $this->appSections['error']->writeln($this->helpers['formatter']->formatSection(
                        sprintf('<%s>%s</%s>', $prefix, $this->currentStep, $prefix),
                        $message
                    ));
                } else {
                    $this->appSections['error']->writeln($message);
                }
            }
        }
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