<?php

namespace FL\Api\Tool\Command\Log;

class Consumer
{

    protected $_topic;

    public function start()
    {
        $config = \Kafka\ConsumerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(1000);
        $config->setMetadataRequestTimeoutMs(1000);

        $config->setMetadataBrokerList('localhost:9092');
        $config->setGroupId($this->_topic);
        //$config->setBrokerVersion('0.9.0.1');
        $config->setTopics(array($this->_topic));
        //$config->setOffsetReset('earliest');
        $consumer = new \Kafka\Consumer();

        $consumer->start(function($topic, $part, $message) {
            var_dump($message);
        });


    }

    public function setTopic($value)
    {
        $this->_topic = $value;
        return $this;
    }
}