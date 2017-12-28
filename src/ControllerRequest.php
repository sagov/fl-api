<?php

namespace FL\Api;

class ControllerRequest
{
    protected $id;


    public function setId()
    {
        $this->id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }
}