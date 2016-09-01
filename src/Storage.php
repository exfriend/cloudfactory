<?php

namespace Exfriend\CloudFactory;


class Storage
{

    private $data = [];

    public function set($key, $value)
    {
        $this->data[ $key ] = $value;
    }

    public function get($key, $default = null)
    {
        if (isset($this->data[ $key ])) {
            return $this->data[ $key ];
        }

        if ($default) {
            return $default;
        }

        return false;
    }

    public function all()
    {
        return $this->data;
    }

}