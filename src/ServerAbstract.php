<?php

namespace FL\Api;

use FL\Config\Config;
use FL\Log\ConfigLog;
use FL\Log\Logger;

abstract class ServerAbstract
{
    protected $_stat;

    public function __construct($config)
    {
        $this->_stat['MICROTIME'][__FUNCTION__] = microtime(true);
        $flconfig = Config::getInstance([]);
        $flconfig->merge(new Config($config));


        $config_api = $flconfig->get('fl-api');
        $config_acl = $config_api->get('acl');
        if (!$config_acl->get('enabled',false)){
            $message = 'Сервис находится на сервисном обслуживании. Приносим извинения за доставленные неудобства';
            if (!empty($config_acl->get('off-datatime'))){
                $utime = strtotime($config_acl->get('off-datatime'));
                if (mktime() < $utime){
                    $date = date("Y-m-d H:i:s", $utime);
                    $message = sprintf('Сервис находится на сервисном обслуживании до %s. Приносим извинения за доставленные неудобства',$date);
                }
            }
            throw new \Exception($message,502);
            return false;
        }

        Api::run();

    }

    abstract public function handle();

    public function __destruct()
    {

        // установка логирования запросов
        $config = Config::getInstance();
        $config_api = $config->get('fl-api');
        $config_api_log = $config_api->get('log')->get('request');
        $configlog = new ConfigLog($config_api_log);
        if ($configlog->isEnabled()){

            $writers = $configlog->getWriters();
            if (count($writers)>0){
                $logger = new Logger();
                foreach ($writers as $writer){
                    $logger->addWriter($writer);
                }
                $info = [];
                $request = new \OAuth2\Request();
                $requestinfo = [];
                $requestinfo['content'] = $request->getContent();
                $requestinfo['headers'] = $request->headers;
                $responseinfo = [];
                if (headers_sent()) {
                    $responseinfo['headers'] = headers_list();
                }
                $responseinfo['code'] = http_response_code();

                $debug['backtrace'] = debug_backtrace();
                $this->_stat['MICROTIME'][__FUNCTION__] = microtime(true);

                $server['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
                $server['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
                $server['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];



                $info['request'] = $requestinfo;
                $info['response'] = $responseinfo;
                $info['trace'] = $debug;
                $info['stat'] = $this->_stat;
                $info['stat'] = $server;

                $logger->info($info);
            }
        }
    }
}