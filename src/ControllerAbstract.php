<?php

namespace FL\Api;



abstract class ControllerAbstract
{

    private $request;

    public function __construct($options = [])
    {
       $this->request = new ControllerRequest();
       if (isset($options['id'])){
           $this->request->setId($options['id']);
       }
    }

    protected function getRequest()
    {
        return $this->request;
    }


}