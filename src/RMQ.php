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
        $this->instance = Connect::getInstance();
    }

    public function publish(string $queue, array $payload)
    {
        if (empty($payload["tries"])) $payload["tries"] = 1;
        $data = json_encode($payload);
        return $this->instance->lpush($queue, [$data]);
    }


    public function consumeWorker(Worker $workerClass, string $queue, $delay = 5, $requeue = false, $max_tries = 3)
    {
        while (true) {
            $msg = $this->instance->rpop($queue);
            $data = json_decode($msg, true);
            try {
                if ($data) $workerClass->handle($data);

            } catch (\Exception $e) {
                $requeued = $this->analyzeRequeue($data, $queue, $requeue, $max_tries);

                if (!$requeued) $workerClass->error($data);
            }
            sleep($delay);
        }
    }

    public function analyzeRequeue($data, $queue, $requeue, $max_tries)
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
}