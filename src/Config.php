<?php

namespace GBublik\Lpdt;

class Config implements \ArrayAccess
{
    protected $defaultConfig = [
        'tick_scale' => 10,
        'default_host' => 'localhost',
        'default_port' => 8082,
        'socket_enabled' => false,
        'log' => [
            'enabled' => false,
            'file' => null,
            'append' => false
        ]
    ];

    protected $config;

    public function __construct()
    {
        $this->config = $this->defaultConfig;
        if ($jsonConfig = $this->getJsonConfig()) {
            $this->config = $this->mergeArray($this->config, $jsonConfig);
        }
    }

    protected function getJsonConfig()
    {
        $jsonFile = dirname($_SERVER['SCRIPT_FILENAME']) . '/lpdt.json';

        if (file_exists($jsonFile))
            return $this->objToArray(
                json_decode(
                    file_get_contents($jsonFile)
                )
            );
        else if (file_exists((isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] .'/' : '') . 'lpdt.json'))
            return $this->objToArray(
                json_decode(
                    file_get_contents((isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] .'/' : '') . 'lpdt.json')
                )
            );
        return [];
    }

    protected function objToArray($config)
    {
        $config = (array)$config;
        foreach ($config as &$value) {
            if (is_object($value)) $value = $this->objToArray($value);
        }
        return $config;
    }

    protected function mergeArray(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (isset($a[$key]) || array_key_exists($key, $a)) {
                if (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = $this->mergeArray($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }
        return $a;
    }

    public function set($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
    }

    public function get($key)
    {
        return $this->config[$key];
    }

    public function offsetExists($offset)
    {
        return key_exists($offset, $this->config);
    }

    public function offsetGet($offset)
    {
        return $this->config[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->config[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }
}