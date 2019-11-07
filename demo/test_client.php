<?php
include "header.php";
$fp = stream_socket_client("tcp://localhost:8081", $errno, $errstr, 30);
$console = fopen('php://stdout', 'w');

if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
    $request = '';
    if (!isset($argv[1])) {
        $argv[1] = 'Default';
    }
    $request .= $argv[1] . (isset($argv[2]) ? ' ' . $argv[2] : '');
    $request .= "\r\nUpgrade: Cli\r\n";

    fwrite($fp, $request);

    while (!feof($fp)) {
        $f = fgets($fp, 1024);
        fwrite($console, $f);
    }
    fclose($fp);
}
fclose($console);