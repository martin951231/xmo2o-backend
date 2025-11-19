<?php

namespace service;

class RedisService extends \Redis
{
    public function __construct()
    {
        $option = C('redis');
        parent::__construct($option);

        $this->connect($option['host'], $option['port']);
        $this->auth($option['password']);
    }

}