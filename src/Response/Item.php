<?php

namespace  FL\Api\Response;

class Item
{
    protected $_item;

    public function assign($name,$value)
    {
        $this->_item[$name] = $value;
        return $this;
    }

    public function get()
    {
        return $this->_item;
    }
}