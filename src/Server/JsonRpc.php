<?php

namespace FL\Api\Server;

use FL\Api\Api;
use FL\Api\Application;
use FL\Api\ServerAbstract;
use FL\Config\Config;
use Zend\Json\Server\Server;

class JsonRpc extends ServerAbstract
{
    protected $_json = [];

    private $_namespace_module;

    private $_path_module;

    protected $_return_response = false;


    public function handle()
    {
        $config = Config::getInstance();
        $config_api = $config->get('fl-api');
        $config_apimodule = $config_api->get('module');

        $this->_namespace_module = $config_apimodule->get('namespace');
        $this->_path_module = $config_apimodule->get('path');

        if (isset($this->_json[0])) {
            $response = [];
            foreach ($this->_json as $jsonitem) {
                $response[] = $this->handleItem($jsonitem);
            }
        } else {
            $response = $this->handleItem($this->_json);
        }
        if ($this->_return_response) {
            return $response;
        }
        header('Content-Type: application/json; charset=UTF-8');

        if (is_array($response)) {
            echo '['.implode(',',$response).']';
        }else{
            echo $response;
        }
    }

    public function loadJson($jsontext)
    {
        $this->_json = \Zend\Json\Json::decode($jsontext, \Zend\Json\Json::TYPE_ARRAY);
        return $this;
    }

    protected function handleItem($options)
    {

        if (empty($options['method'])) {
            throw  new \Exception('Method empty');
        }

        $json_method = $options['method'];
        $method_stack = explode('.', $json_method);

        $method = array_pop($method_stack);
        $options['method'] = $method;

        if (count($method_stack) == 0) {
            throw  new \Exception('Method not defined');
        }

        $apimodule = ucwords(array_shift($method_stack));

        $module = $this->getApplication($apimodule);
        $module_config = $module->getConfig();


        // определяем версию
        $version = ucwords($this->_getVersion());
        if (empty($version)) {
            $version = $module->getVersion();
        }

        $controller_path = null;
        if (count($method_stack) == 0) {
            $controller = 'Index';
        } else {
            $controller = ucwords(array_pop($method_stack));
            if (count($method_stack) > 0) {
                $controller_path = implode('\\', $method_stack) . '\\';
                $controller_path = str_replace(' ', '\\',
                    ucwords(implode(' ', $controller_path)));
            }
        }

        $classname = '\\' . $this->_namespace_module . '\\' . $apimodule . '\\Controller\\' . $version . '\\' . $controller_path . $controller . 'Controller';
        if (!class_exists($classname)) {
            throw new \Exception('API service not define');
            return;
        }

        if (isset($module_config['acl'])) {
            if (!Api::isAllowed($module_config['acl'])) {
                throw new \Exception('API module access denied');
                return;
            }
        }

        $service_config = (isset($module_config['src'][$classname])) ? $module_config['src'][$classname] : [];

        if (isset($service_config['acl'])) {
            if (!Api::isAllowed($service_config['acl'])) {
                throw new \Exception('API service access denied');
                return;
            }
        }

        $method_config = (isset($service_config['methods'][$method])) ? $service_config['methods'][$method] : [];

        if (isset($method_config['acl'])) {
            if (!Api::isAllowed($method_config['acl'])) {
                throw new \Exception('Access denied API method ');
                return;
            }
        }

        // создание сервера
        $jsonrpc = $this->getServer($classname,$options);

        $cache_response = $module->getCacheResponse($classname, $jsonrpc->getRequest()->getMethod(), $jsonrpc->getRequest()->getParams());
        $response_result = $cache_response->getResult();

        if ($cache_response->is() and isset($response_result)) {
            $request = $jsonrpc->getRequest();
            $response = $jsonrpc->getResponse();
            $response->setServiceMap($jsonrpc->getServiceMap());
            if (null !== ($id = $request->getId())) {
                $response->setId($id);
            }
            if (null !== ($version = $request->getVersion())) {
                $response->setVersion($version);
            }
            $response->setResult($response_result);
        } else {

            if (!Api::isValidParams($jsonrpc->getRequest()->getParams(), $method_config, $detail)) {
                throw new \Exception(implode(', ', $detail['messages']));
                return;
            }

            $response = $jsonrpc->handle(new \FL\Json\Server\Request\ArrayFormat($options));
            if ($cache_response->is()) {
                $response_result = $jsonrpc->getResponse()->getResult();
                $cache_response->save($response_result);
            }

        }
        return $response;
    }

    protected function getServer($classname,$options)
    {
        $jsonrpc = new Server();
        $jsonrpc->setClass(new $classname($options));
        $jsonrpc->getRequest()->setVersion(Server::VERSION_2);
        $jsonrpc->setReturnResponse(true);
        return  $jsonrpc;
    }

    protected function getApplication($application)
    {
        static $result;

        if (isset($result[$application])) {
            return $result[$application];
        }
        $appConfig = [
            'name' => $application,
            'namespace' => $this->_namespace_module,
            'path' => $this->_path_module,
        ];
        return $result[$application] = new Application($appConfig);
    }

    protected function _getVersion()
    {
        $http_accept = $_SERVER['HTTP_ACCEPT'];
        $version = null;
        if (stripos($http_accept, 'vnd') !== false) {
            $version = str_replace('application/', null, $http_accept);
            $version = str_replace('vnd.', null, $version);
            $version = str_replace('+json', null, $version);
        }
        return $version;
    }

    /**
     * @param $flag
     */
    public function setReturnResponse($flag)
    {
        $this->_return_response = (boolean)($flag);
        return $this;
    }
}