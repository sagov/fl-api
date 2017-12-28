<?php

namespace FL\Api;

use FL\Api\Application\CacheResponse;

class Application
{
    protected $_name;

    protected $_path;

    protected $_namespace;

    protected $_module;

    public function __construct($config)
    {
        $this->_name = $config['name'];
        $this->_namespace = $config['namespace'];
        $this->_path = $config['path'];
    }

    public function getObject()
    {
        if (isset($this->_module)){
            return $this->_module;
        }
        if (!file_exists($this->_path .'/'.$this->_name.'/Module.php')){
            throw new \Exception('Api module '.$this->_name.' not found');
        }
        require_once $this->_path .'/'.$this->_name.'/Module.php';
        $classname = $this->_namespace . '\\' . $this->_name . '\\Module';

        if (!class_exists($classname)){
            throw new \Exception('Api class '.$classname.' not found');
        }

        $this->_module = new $classname();
        return $this->_module;
    }

    public function getConfig()
    {
        $module = $this->getObject();
        return $module->getConfig();
    }

    public function getVersion()
    {
        $config = $this->getConfig();
        return $config['version'];
    }

    public function getCacheResponse($class,$method,$args)
    {
        return new CacheResponse($this,$class,$method,$args);
    }



}