<?php
namespace GBublik\Lpdt;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputDefinition;

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

        return new InputDefinition($definition);
    }
}
