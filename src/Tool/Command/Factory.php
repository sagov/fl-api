<?php

namespace FL\Api\Tool\Command;

class Factory
{
    const TEMPLATE_MODULE = <<<'EOT'
<?php

namespace %s\%s;

use FL\Api\InterfaceModule;

class Module implements InterfaceModule
{
    public function getConfig()
    {
        return include __DIR__.'/config/module.config.php';
    }
}

EOT;


    const TEMPLATE_MODULE_CLASS = <<<'EOT'
<?php

namespace %s\%s\Controller\V1;

use FL\Api\ControllerAbstract;

class IndexController extends ControllerAbstract
{
    public function test()
    {
        return true;
    }
}

EOT;

    const TEMPLATE_MODULE_CONFIG = <<<'EOT'
<?php

return [
    'version' => 'V1',
    'name' => '',
    'descshort' => '',
    'description' => '',
    'icon' => '',
    'acl' => [
        'permission' => '',
    ],
    'src' => [
        %s\%s\Controller\V1\IndexController::class => include __DIR__.'/Controller/V1/IndexController.php',
    ],
    'servers' => [
        'jsonrpc',        
    ],    

];

EOT;


    const TEMPLATE_MODULE_CONFIG_CLASS = <<<'EOT'
<?php

return [
    'name' => '',
    'description' => '',
    'acl' => [
        'permission' => '',
    ],
    'methods' => [
        'test' => include __DIR__.'/IndexController.test.php',           
    ],    
];

EOT;

    const TEMPLATE_MODULE_CONFIG_CLASS_METHOD = <<<'EOT'
<?php

return [
     'return' => \FL\Api\Response\Type::BOOLEAN,   
     'phpunit' => true,
];

EOT;


    const TEMPLATE_MODULE_TEST_CLASS = <<<'EOT'
<?php

namespace %sTest\%s\Controller\V1;

use PHPUnit\Framework\TestCase;

class IndexController extends TestCase
{
    public function test()
    {
        $service = new \%s\%s\Controller\V1\IndexController();
        $this->assertEquals(true,$service->test());
    }
}

EOT;

}