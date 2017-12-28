<?php

namespace  FL\Api\Server;

use FL\Api\Application;
use FL\Api\ServerAbstract;
use FL\Config\Config;
use FL\Api\Api;

class Soap extends ServerAbstract
{
    protected $_module;

    protected $_service;



    public function setModule($name)
    {
        $this->_module = $name;
        return $this;
    }

    public function setService($name)
    {
        $this->_service = $name;
        return $this;
    }

    protected function _getVersion()
    {
        $http_accept = $_SERVER['HTTP_ACCEPT'];
        $version = null;
        if (stripos($http_accept, 'vnd') !== false) {
            $version = str_replace('application/', null, $http_accept);
            $version = str_replace('vnd.', null, $version);
            $version = str_replace('+xml', null, $version);
        }
        return $version;
    }


    public function handle($request = false)
    {
        if (empty($this->_service)) {
            $this->_service = $this->_module;
        }

        $config = Config::getInstance();
        $config_api = $config->get('fl-api');
        $config_apimodule = $config_api->get('module');
        $config_apimodule->get('namespace');

        // Определение название модуля и класса
        $appConfig = [
            'name' => $this->_module,
            'namespace' => $config_apimodule->get('namespace'),
            'path' => $config_apimodule->get('path'),
        ];
        $module = new Application($appConfig);
        $module_config = $module->getConfig();

        // определяем версию
        $version = $this->_getVersion();
        if (empty($version)) {
            $version = $module->getVersion();
        }

        // имя класса
        $classname = $appConfig['namespace'] . '\\' . $this->_module . '\\' . strtoupper($version) . '\\' . $this->_service;
        if (!class_exists($classname)) {
            throw new \Exception('API service not define');
            return;
        }


        // отключаем кэширование WSDL
        ini_set("soap.wsdl_cache_enabled", "0");
        $Uri = $config_api->get('domen') . str_replace('//', '/', $_SERVER['PHP_SELF']);

        if (isset($_GET['wsdl'])) {
            if (!empty( $this->_additionuri)) {
                $Uri .= '?' .  $this->_additionuri;
            }
            $autodiscover = new \Zend\Soap\AutoDiscover();
            $autodiscover->setClass($classname);
            $autodiscover->setUri($Uri);
            $autodiscover->handle();
        } else {

            Api::run();
            if (isset($module_config['acl'])){
                if (!Api::isAllowed($module_config['acl'])){
                    throw new \Exception('API service access denied');
                    return;
                }
            }

            $Uri .= '?wsdl';
            if (!empty( $this->_additionuri)) {
                $Uri .=  '&'.$this->_additionuri;
            }

            $soap = new \Zend\Soap\Server($Uri);
            $soap->setWsdlCache(false);
            $soap->setClass($classname);
            $soap->handle();
        }
        return;
    }

    public function setAdditionUri($value)
    {
        $this->_additionuri = $value;
        return $this;
    }
}