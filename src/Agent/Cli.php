<?php

namespace GBublik\Lpdt\Agent;

class Cli extends AgentBase
{
    public function write($str, $serverInfo)
    {
        if ($this->request->getCommand() == 'default') {
            $this->writeToSocket(($serverInfo['current_step'] ? $serverInfo['current_step'] . ': ' : '') . $str);
        }
    }

    protected function writeToSocket($str)
    {
        @socket_write($this->socket, $str . "\r\n");
        $error = socket_last_error($this->socket);
        if ($error > 0 && $error != 10035) {
            throw new AgentException('Client disconnected', $error);
        }
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function disconnect($msg = '')
    {
        socket_close($this->socket);
        throw new AgentException('Client disconnected');
    }

    public function tick(&$serverInfo = [])
    {
        switch ($this->request->getCommand()) {
            case 'steps':
                if (count($serverInfo['executed_step']) > 0) {
                    foreach ($serverInfo['executed_step'] as $step) {
                        $this->writeToSocket($step . ': finish');
                    }
                }
                if (!empty($serverInfo['current_step'])) {
                    $this->writeToSocket($serverInfo['current_step'] . ': execute');
                } else {
                    $this->writeToSocket('Steps don\'t used');
                }
                $this->disconnect();
                break;
            case 'memory' :
                $this->writeToSocket(
                    $this->replaceOut("Memory usage: " . $this->convert(memory_get_peak_usage()) . "(" . $this->memoryLimit . ")")
                );
                break;
            case 'info':
                $info = $this->getServerInfo($serverInfo);

                if (empty($this->request->getParam('realtime'))) {
                    $this->writeToSocket($info);
                    $this->disconnect();
                } else {
                    $this->writeToSocket($this->replaceOut($info));
                }
                break;
            case 'default':
                break;
            default:
                $this->writeToSocket('Command don\'t support');
                $this->disconnect();
                break;
        }
    }

    protected function getServerInfo($serverStatistic)
    {
        $msg = '';
        $addInfo = '';

        $separator = "\r\n";
        $dateNow = new \DateTime();

        /** @var \DateTime $startTime */
        $startTime = $serverStatistic['start_time'];
        if ($serverStatistic['current_step'])
            $msg .= 'Current step: ' . $serverStatistic['current_step'] . "\r\n";
        $msg .= "Time start: " . $startTime->format('d.m.Y H:i:s');
        $msg .= "\r\nTime now: " . $dateNow->format('d.m.Y H:i:s');
        $msg .= "\r\nDuration: " . $startTime->diff($dateNow)->format('%i min.');
        $msg .= $separator;
        $msg .= "\r\nOS: " . php_uname();
        $msg .= "\r\nMemory limit: " . $this->memoryLimit . "B";
        $msg .= "\r\nPick memory usage: " . $this->convert($serverStatistic['pick_memory_usage']);
        $msg .= "\r\nCurrent memory usage: " . $this->convert(memory_get_peak_usage());
        $msg .= "\r\nGID: " . getmygid();
        $msg .= "\r\nPID: " . getmypid();
        $msg .= "\r\nPHP version: " . phpversion();
        $msg .= "\r\nProcess title:" . cli_get_process_title();

        if (!empty($this->request->getParam('full'))) {
            $addInfo .= $separator;
            if (function_exists('sys_getloadavg')) {
                $cpu = sys_getloadavg();
                $addInfo .= "\r\nCPU: " . (($cpu[0] + $cpu[1] + $cpu[2]) / 3);
            }
            $info = getrusage();
            if (!empty($info['ru_oublock']))
                $addInfo .= "\r\nКоличество операций вывода блока: " . ($info['ru_oublock'] ?: '-');
            if (!empty($info['ru_inblock']))
                $addInfo .= "\r\nКоличество операций приема блока: " . ($info['ru_inblock'] ?: '-');
            if (!empty($info['ru_msgsnd']))
                $addInfo .= "\r\nКоличество отправленных сообщений IPC: " . ($info['ru_msgsnd'] ?: '-');
            if (!empty($info['ru_msgrcv']))
                $addInfo .= "\r\nКоличество принятых сообщений IPC: " . ($info['ru_msgrcv'] ?: '-');
            if (!empty($info['ru_maxrss']))
                $addInfo .= "\r\nНаибольший размер установленной резидентной памяти: " . ($this->convert($info['ru_maxrss']) ?: '-');
            if (!empty($info['ru_ixrss']))
                $addInfo .= "\r\nСуммарное значение размера разделяемой памяти: " . ($info['ru_ixrss'] ?: '-');
            if (!empty($info['ru_idrss']))
                $addInfo .= "\r\nСуммарное значение размера неразделяемых данных: " . ($info['ru_idrss'] ?: '-');
            if (!empty($info['ru_minflt']))
                $addInfo .= "\r\nКоличество исправленных страниц (легкая ошибка страницы): " . ($info['ru_minflt'] ?: '-');
            if (!empty($info['ru_majflt']))
                $addInfo .= "\r\nКоличество ошибочных страниц (тяжелая ошибка страницы): " . ($info['ru_majflt'] ?: '-');
            if (!empty($info['ru_nsignals']))
                $addInfo .= "\r\nКоличество полученных сигналов: " . ($info['ru_nsignals'] ?: '-');
            if (!empty($info['ru_nvcsw']))
                $addInfo .= "\r\nКоличество согласованных переключений контекста: " . ($info['ru_nvcsw'] ?: '-');
            if (!empty($info['ru_nivcsw']))
                $addInfo .= "\r\nКоличество несогласованных переключений контекста: " . ($info['ru_nivcsw'] ?: '-');
            if (!empty($info['ru_nswap']))
                $addInfo .= "\r\nКоличество свопов: " . ($info['ru_nswap'] ?: '-');
            if (!empty($info['ru_utime.tv_usec']))
                $addInfo .= "\r\nВремя на задачи пользователя (user time) (микросекунды): " . ($info['ru_utime.tv_usec'] ?: '-');
            if (!empty($info['ru_utime.tv_sec']))
                $addInfo .= "\r\nВремя на задачи пользователя (user time) (секунды): " . ($info['ru_utime.tv_sec'] ?: '-');
            if (!empty($info['ru_stime.tv_usec']))
                $addInfo .= "\r\nВремя на системные задачи (system time) (микросекунды): " . ($info['ru_stime.tv_usec'] ?: '-');
        }
        return $msg . $addInfo;
    }

    function replaceOut($str)
    {
        $numNewLines = substr_count($str, "\n");
        $out = chr(27) . "[0G"; // Set cursor to first column
        $out .= $str;
        $out .= chr(27) . "[" . $numNewLines . "A"; // Set cursor up x lines

        return $out;
    }

    protected function convert($num)
    {
        $neg = $num < 0;
        $units = array('B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');

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