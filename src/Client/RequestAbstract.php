<?php

namespace FL\Api\Client;

abstract class RequestAbstract
{
    protected $_item = [];

    /**
     * @var \FL\Api\Client\ResponseAbstract
     */
    protected $response;

    protected $url;

    public function __construct($url,$response = null)
    {
        $this->url = $url;

        if (isset($response)){
            $this->setResponse($response);
        }
    }

    abstract public function send();

    public function addItem($id,$method, $arguments = null)
    {
        $this->_item[] = ['method'=>$method,'arguments'=>$arguments,'id'=>$id];
        return $this;
    }

    public function setResponse(\FL\Api\Client\ResponseAbstract $response)
    {
        $this->response = $response;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }
}