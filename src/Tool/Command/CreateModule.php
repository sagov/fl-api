<?php

namespace FL\Api\Tool\Command;

class CreateModule
{
    protected $_path;

    protected $_namespace;

    public function __construct($path,$namespace)
    {
        $this->_path = $path;
        $this->_namespace = $namespace;
    }


    /**
     * @param string $className
     * @return string
     */
    private function getContentModule($moduleName)
    {
        return sprintf(
            Factory::TEMPLATE_MODULE,
            $this->_namespace,
            $moduleName
        );


    }

    private function getContentClass($moduleName)
    {
        return sprintf(
            Factory::TEMPLATE_MODULE_CLASS,
            $this->_namespace,
            $moduleName
        );


    }

    private function getContentTestClass($moduleName)
    {
        return sprintf(
            Factory::TEMPLATE_MODULE_TEST_CLASS,
            $this->_namespace,
            $moduleName,
            $this->_namespace,
            $moduleName
        );


    }

    private function getContentConfig($moduleName)
    {
        return sprintf(
            Factory::TEMPLATE_MODULE_CONFIG,
            $this->_namespace,
            $moduleName
        );


    }

    private function getContentConfigClass()
    {
        return sprintf(
            Factory::TEMPLATE_MODULE_CONFIG_CLASS
        );


    }

    private function getContentConfigClassMethod()
    {
        return sprintf(
            Factory::TEMPLATE_MODULE_CONFIG_CLASS_METHOD
        );

    }

    public function createModule($moduleName)
    {
        if (is_dir($this->_path.'/'.$moduleName)){
            throw  new \Exception('Dir '.$moduleName.' exist');
            return;
        }

        mkdir($this->_path.'/'.$moduleName, 0700);
        file_put_contents($this->_path.'/'.$moduleName.'/Module.php',$this->getContentModule($moduleName),0);
        mkdir($this->_path.'/'.$moduleName.'/src', 0700);
        mkdir($this->_path.'/'.$moduleName.'/src/Controller', 0700);
        mkdir($this->_path.'/'.$moduleName.'/src/Factory', 0700);
        mkdir($this->_path.'/'.$moduleName.'/src/Model', 0700);
        mkdir($this->_path.'/'.$moduleName.'/src/Controller/V1', 0700);
        mkdir($this->_path.'/'.$moduleName.'/config', 0700);
        mkdir($this->_path.'/'.$moduleName.'/config/Controller', 0700);
        mkdir($this->_path.'/'.$moduleName.'/config/Controller/V1', 0700);
        mkdir($this->_path.'/'.$moduleName.'/test', 0700);
        mkdir($this->_path.'/'.$moduleName.'/test/Controller', 0700);
        mkdir($this->_path.'/'.$moduleName.'/test/Controller/V1', 0700);
        file_put_contents($this->_path.'/'.$moduleName.'/src/Controller/V1/IndexController.php',$this->getContentClass($moduleName),0);
        file_put_contents($this->_path.'/'.$moduleName.'/config/module.config.php',$this->getContentConfig($moduleName),0);
        file_put_contents($this->_path.'/'.$moduleName.'/config/Controller/V1/IndexController.php',$this->getContentConfigClass(),0);
        file_put_contents($this->_path.'/'.$moduleName.'/config/Controller/V1/IndexController.test.php',$this->getContentConfigClassMethod(),0);
        file_put_contents($this->_path.'/'.$moduleName.'/test/Controller/V1/IndexController.php',$this->getContentTestClass($moduleName),0);
    }



}