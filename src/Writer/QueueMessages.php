<?php

namespace GBublik\Lpdt\Writer;

class QueueMessages implements \ArrayAccess, \Iterator
{
    protected $queue = [];

    /** @var int Размер очереди */
    protected $size;

    public function __construct(int $size)
    {
        $this->size = $size;
    }

    public function offsetExists($offset)
    {
        return isset($this->queue[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->queue[$offset]) ? $this->queue[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            if (count($this->queue) >= $this->size) array_shift($this->queue);
            $this->queue[] = $value;
        } else {
            $this->queue[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->queue[$offset]);
    }

    public function current()
    {
        return current($this->queue);
    }

    public function next()
    {
        return next($this->queue);
    }

    public function key()
    {
        return $this->key($this->queue);
    }

    public function valid()
    {
        $key = key($this->queue);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

    public function rewind()
    {
        reset($this->queue);
    }
}