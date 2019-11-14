<?php
namespace GBublik\Lpdt;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class Application extends SymfonyApplication
{
    const DEFAULT_OPTIONS = [
        'help',
        'list',
        //'quiet',
        'version',
        'no-interaction'
    ];

    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
    }

    protected function getDefaultInputDefinition()
    {
        $definition  = [];

        /** @var InputDefinition $defaultDefinition */
        $defaultDefinition = parent::getDefaultInputDefinition();
        foreach ($defaultDefinition->getOptions() as $option) {
            if (in_array($option->getName(), self::DEFAULT_OPTIONS)) {
                $definition[] = $option;
            }
        }

        foreach ($defaultDefinition->getArguments() as $argument) {
            $definition[] = $argument;
        }
        $definition[] = new InputOption(
            'log-file',
            '-f',
            InputOption::VALUE_OPTIONAL,
            'Файл в котором будет записываться лог PSR-3'
        );
        $definition[] = new InputOption(
            'log-overwrite',
            '-o',
            InputOption::VALUE_OPTIONAL,
            'Перезаписывать файл лога',
            false
        );
        $definition[] = new InputOption(
            'level',
            '-l',
            InputOption::VALUE_OPTIONAL,
            'Уровень логирования',
            'DEBUG'
        );
        $definition[] = new InputOption(
            'log-days',
            '-d',
            InputOption::VALUE_OPTIONAL,
            'Дней хранить файлы лога',
            5
        );
        $definition[] = new InputOption(
            'queues-errors',
            '-e',
            InputOption::VALUE_OPTIONAL,
            'Error section size',
            null
        );
        $definition[] = new InputOption(
            'queues-messages',
            '-m',
            InputOption::VALUE_OPTIONAL,
            'Information section size',
            1
        );
        $definition[] = new InputOption(
            'quiet',
            '-q',
            InputOption::VALUE_OPTIONAL,
            'Do not output any message',
            false
        );
        $definition[] = new InputOption(
            'light',
            '-t',
            InputOption::VALUE_OPTIONAL,
            'Light mode',
            false
        );

        return new InputDefinition($definition);
    }
}