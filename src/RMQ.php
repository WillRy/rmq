<?php

namespace WillRy\RMQ;

use Predis\Client;

class RMQ
{
    /** @var Client $instance */
    public $instance = null;

    public $queueName;

    public $queue;

    public function __construct($queueName, $host, $port = 6379, $persistent = "1")
    {
        Connect::config($host, $port, $persistent);
        $this->instance = Connect::getInstancePredis();
        $this->queueName = $queueName;
        $this->queue = $this->getQueueName($queueName);
    }

    public function getQueueName(string $queue)
    {
        return "queue:{$queue}";
    }

    public function getHashName($id)
    {
        return "queue:{$this->queueName}:{$id}";
    }


    public function analyzeRequeue($data, $requeue, $max_tries)
    {
        if (empty($data)) return false;

        if (empty($data["tries"])) $data["tries"] = 1;

        $notMax = (int)$data["tries"] < $max_tries;

        if ($requeue && $notMax) {
            $data["tries"] = (int)$data["tries"] + 1;

            $this->publish($data);

            return true;
        }

        return false;
    }

    public function excludeQueue($queue)
    {
        return $this->instance->del($queue);
    }

    public function decode($msg)
    {

        if (!is_array($msg) || empty(array_keys($msg))) return [];

        $data = json_decode($msg[0], true);


        if (empty($data)) return [];

        $result = [];
        foreach ($data as $key => $item) {
            $result[$key] = $this->isJson($item) ? json_decode($item, true) : $item;
        }

        return $result;
    }

    public function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function encode($payload)
    {
        $data = [];
        foreach ($payload as $key => $item) {
            if (is_array($item)) {
                $data[$key] = json_encode($item);
            } else {
                $data[$key] = $item;
            }
        }
        return $data;
    }


}