<?php

namespace FL\Api\Client;

abstract class ResponseAbstract
{
    protected $_content;

    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    public function getContent()
    {
        return $this->_content;
    }
}