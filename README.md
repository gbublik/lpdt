# Large proccess debug tool 1.0

### INSTALL

`composer require gbublik/lpdt:dev-master`

### Exemple

## Debug long process on backend
```php
<?php
require __DIR__ . '/vendor/autoload.php';

$config = new Gbublik\Lpdt\Config();
$config['socket_enabled'] = true;

$log = Gbublik\Lpdt\LogServer::getInstance('localhost', 8081, $config);

$i = 0;
while(true) {
    if ($i % 100000) {
        $log->write('Working...');
    }
    $i++;
}
$s->stop();
```

## CLI client

### Telnet
```console
user@user:~$ telnet -r localhost 8081
Trying 127.0.0.1...
Connected to localhost.
Escape character is '^]'.
Upgrade: Cli

Working...
Working...
Working...
```

### php cli client
```php
<?php
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
```
