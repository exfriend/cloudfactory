<?php

namespace Exfriend\CloudFactory;


class Options
{
    /**
     * @var array
     * curl options
     */
    protected $options = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 7,
        CURLOPT_RETURNTRANSFER => true,
    ];

    public function set($key, $value)
    {
        $this->options[ $key ] = $value;
    }

    public function get($key = null, $default = null)
    {
        if (!$key) {
            return $this->options;
        }
        return isset($this->options[ $key ]) ? $this->options[ $key ] : $default;
    }


    public function merge($array)
    {
        $this->options = $array + $this->options;
        return $this;
    }

}