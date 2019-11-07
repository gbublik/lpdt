<?php

use GBublik\Lpdt\Config;
use GBublik\Lpdt\LogServer;

include_once 'header.php';

$config = new Config();
$config['socket_enabled'] = true;

$log = LogServer::getInstance('localhost', 8081, $config);

$i = 0;
while(true) {
    if ($i % 100000) {
        $log->write('zalpa');
    }
    $i++;
}