<?php

namespace WillRy\RMQ;

use Predis\Client;

class RMQ
{
    /** @var Client $instance */
    public $instance = null;

    public function __construct($host, $port = 6379, $persistent = "1")
    {
        Connect::config($host, $port, $persistent);
        $this->instance = Connect::getInstancePredis();
    }

    public function analyzeRequeue($data, $queue, $requeue, $max_tries, $list = true)
    {
        if (empty($data)) return false;

        if (empty($data["tries"])) $data["tries"] = 1;

        $notMax = (int)$data["tries"] < $max_tries;

        if ($requeue && $notMax) {
            $data["tries"] = (int)$data["tries"] + 1;

            $this->publish($queue, $data);

            return true;
        }

        return false;
    }

    public function excludeQueue($queue)
    {
        return $this->instance->del($queue);
    }

    public function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function decode($msg)
    {

        if (!is_array($msg) || empty(array_keys($msg))) return [];

        $data = json_decode($msg[0], true);


        if(empty($data["id"])) return [];

        $result = [];
        foreach ($data as $key => $item) {
            $result[$key] = $this->isJson($item) ? json_decode($item, true) : $item;
        }

        return $result;
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