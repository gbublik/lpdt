<?php
namespace GBublik\Lpdt;

class Request
{
    protected $body;

    protected $command;

    protected $params = [];

    protected $headers = [];

    public function __construct($strRequest)
    {
        $this->parseDocument($strRequest);
    }

    protected function parseDocument($strRequest)
    {
        preg_match('/(^.*?)(\r\n\r\n.*)?$/s', $strRequest, $matches);

        $this->body = trim($matches[2]);

        preg_match_all('/(^.*?[^\r\n]+)/ms',$matches[1], $headers);

        foreach ($headers[1] as $header) {
            $ex = explode(':', $header);
            if (count($ex) == 1) { //Команда
                preg_match('/([a-zA-Z]+)\s?(.*)?|$/', $ex[0], $command);

                if (empty($command[1])) throw new RequestException('Bad request');
                $this->command = strtolower($command[1]);

                if (!empty($command[2])) {
                    parse_str($command[2], $this->params);
                }
            } else if(count($ex) == 2)  { //Загаловок
                $key = strtolower(trim($ex[0]));
                $value = trim($ex[1]);
                if (!empty($key) && !empty($value)) $this->headers[$key] = $value;
            }
        }
    }

    public function getCommand(){
        return $this->command ?: 'default';
    }

    public function getParam($key){
        return $this->params[$key];
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getHeader($key) {
        return $this->headers[$key];
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}