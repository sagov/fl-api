<?php

namespace FL\Api\Client\Request;

use FL\Api\Client\RequestAbstract;

class JsonRpc extends RequestAbstract
{

    public function __construct($url,$response = null)
    {
        if (!isset($response)){
            $response = new \FL\Api\Client\Response\JsonRpc();
        }
        parent::__construct($url,$response);
    }

    public function send()
    {
        if (count($this->_item) == 0) {
            return false;
        }

        if (count($this->_item) == 1){
            $request = $this->_item[0];
        }else{
            $request = $this->_item;
        }

        $response = $this->_getResponse($this->url,$request);
        $this->response->setContent($response);
        return $this;
    }


    public function addItem($id,$method, $arguments = null)
    {
        $this->_item[] = ['jsonrpc'=>'2.0','method'=>$method,'params'=>$arguments,'id'=>$id];
        return $this;
    }

    protected function _getResponse($url,$params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer 3445d7d7aea8e2e934d94006449e6d7320764100'
            )
        );
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $result = curl_exec($ch);

        if(curl_error($ch)){
            $result = curl_error($ch);
        }
        curl_close ($ch);
        return $result;
    }
}