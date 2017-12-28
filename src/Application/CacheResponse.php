<?php

namespace FL\Api\Application;

use FL\Cache\Cache;

class CacheResponse
{
    protected $_module;

    protected $_classname;
    protected $_method;
    protected $_args;
    protected $_key;

    protected $_config;
    protected $_adapter;

    public function __construct(\FL\Api\Application $module, $classname, $method, $args)
    {
        $this->_module = $module;
        $this->_classname = $classname;
        $this->_method = $method;
        $this->_args = $args;
        $this->_init();
    }

    protected function _init()
    {
        $this->_config = $this->_getConfig();
        $this->_adapter = (isset($this->_config['adapter'])) ? $this->_config['adapter'] : null;
        $this->_key = $this->_getKey();

    }

    protected function _getConfig()
    {
        $config_module = $this->_module->getConfig();
        if (isset($config_module['src'][$this->_classname]['methods'][$this->_method]['cache'])) {
            return $config_module['src'][$this->_classname]['methods'][$this->_method]['cache'];
        }
        return false;
    }


    protected function _getKey()
    {
        if ($this->is()) {
            return Cache::buildKey([$this->_classname, $this->_method, $this->_args], $this->_adapter);
        }

        return false;
    }



    public function getKey()
    {
        return $this->_key;
    }

    public function getResult()
    {
        if (!$this->is()){
            return ;
        }
        $cache = Cache::factory($this->_adapter);
        if (isset($this->_config['ttl'])) {
            $options = $cache->getOptions();
            $options->setTtl($this->_config['ttl']);
        }

        if ($cache->hasItem($this->_key)) {
            return $cache->getItem($this->_key);
        }

        return ;
    }

    public function save($value)
    {
        if (!$this->is()){
            return false;
        }
        $cache = Cache::factory($this->_adapter);
        return $cache->addItem($this->_key,$value);
    }

    public function is()
    {
        return isset ($this->_config['enabled']) ? $this->_config['enabled'] : false;
    }
}