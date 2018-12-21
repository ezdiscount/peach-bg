<?php

namespace service\config;

use db\SqlMapper;

class Config
{
    const CACHE_TIME = 3600;
    private $string = '';
    protected $name = '';
    protected $data = [];

    function get($key)
    {
        return $this->data[$key];
    }

    function toString()
    {
        return $this->string;
    }

    function __construct()
    {
        $config = new SqlMapper('config');
        list($query) = $config->find(['name=?', $this->name], ['order' => 'version desc', 'limit' => 1], self::CACHE_TIME);
        $this->string = $query['data'];
        $this->data = json_decode($this->string, true);
    }
}
