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

    public function analyzeRequeue($data, $queue, $requeue, $max_tries, $list = true)
    {
        if (empty($data)) return false;

        if (empty($data["tries"])) $data["tries"] = 1;

        $notMax = (int)$data["tries"] < $max_tries;

        if ($requeue && $notMax) {
            $data["tries"] = (int)$data["tries"] + 1;

            if (!$list) {
                $count = $this->instance->zCount($queue, 0, 99999999) + $data["id"];
                $this->publishOrderedSet($queue, $data, $count);
            } else {
                $this->publishList($queue, $data);
            }

            return true;
        }

        return false;
    }

    public function publishList(string $queue, array $payload)
    {
        if (empty($payload["tries"])) $payload["tries"] = 1;
        $data = json_encode($payload);
        return $this->instance->rpush($queue, $data);
    }


    public function consumeList(Worker $workerClass, string $queue, $delay = 5, $requeue = false, $max_tries = 3)
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


    public function publishOrderedSet(string $queue, array $payload, $count = null)
    {
        if (empty($payload["tries"])) $payload["tries"] = 1;
        $count = !$count ? $payload["id"] : $count;
        $data = json_encode($payload);
        return $this->instance->zAdd($queue, [], $count, $data);
    }

    public function consumeOrderedSet(Worker $workerClass, string $queue, $delay = 5, $requeue = false, $max_tries = 3)
    {
        while (true) {
            $data = null;
            $msg = $this->instance->zPopMin($queue);
            if (is_array($msg) && !empty(array_keys($msg))) $data = json_decode(array_keys($msg)[0], true);
            try {
                if ($data) $workerClass->handle($data);

            } catch (\Exception $e) {
                $requeued = $this->analyzeRequeue($data, $queue, $requeue, $max_tries);

                if (!$requeued) $workerClass->error($data);
            }
            sleep($delay);
        }
    }

    public function excludeQueue($queue)
    {
        return $this->instance->del($queue);
    }

    public function getList($queue, $page = 1, $perPage = 100)
    {
        $page = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $perPage;
        $max = 2 * $perPage;
        return $this->instance->lRange($queue, $offset, $max);
    }

    public function getListCount($queue)
    {
        return $this->instance->llen($queue);
    }

    public function getListPages($queue, $perPage = 100)
    {
        $total = $this->getListCount($queue);
        if($total) return ceil($total / $perPage);
        return 0;
    }

    public function getListByID($queue, $id, $delay = 1)
    {
        $pages = $this->getListPages($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getList($queue, $i);

            $item = array_filter($data, function ($item) use ($id) {
                $json = json_decode($item, true);

                return $json['id'] == $id;
            });
            if(!empty($item)) return $item;
            sleep($delay);
        }

        return null;
    }

    public function getListByField(string $queue, string $field, string $value, int $delay = 5)
    {
        $pages = $this->getListPages($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getList($queue, $i);

            $item = array_filter($data, function ($item) use ($field, $value) {
                $json = json_decode($item, true);
                if (empty($json['payload'][$field])) return false;

                if (is_array($json['payload'][$field])) {
                    return in_array($value, $json['payload'][$field]);
                }

                return $json['payload'][$field] == $value;
            });

            if(!empty($item)) return $item;
            sleep($delay);
        }

        return null;
    }


    /**
     * Remove item from List queue
     *
     * @param $queue
     * @param string $id
     */
    public function removeListByID($queue, string $id, $delay = 5)
    {
        $pages = $this->getListPages($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getList($queue, $i);

            $item = array_filter($data, function ($item) use ($id) {
                $json = json_decode($item, true);

                return $json['id'] == $id;
            });
            if(!empty($item)) {
                $this->instance->lRem($queue, array_shift($item), 1);
                return true;
            }
            sleep($delay);
        }

        return false;
    }




    public function getSet($queue, $page = 1, $perPage = 100)
    {
        $page = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $perPage;
        $max = 2 * $perPage;
        return $this->instance->zRange($queue, $offset, $max);
    }

    public function getSetCount($queue)
    {
        return $this->instance->zCount($queue, 0, 9999999999);
    }

    public function getSetPages($queue, $perPage = 100)
    {
        $total = $this->getSetCount($queue);
        if($total) return ceil($total / $perPage);
        return 0;
    }

    public function getSetByID($queue, $id, $delay = 1)
    {
        $pages = $this->getSetCount($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getSet($queue, $i);

            $item = array_filter($data, function ($item) use ($id) {
                $json = json_decode($item, true);

                return $json['id'] == $id;
            });
            if(!empty($item)) return $item;
            sleep($delay);
        }

        return null;
    }

    public function getSetByField(string $queue, string $field, string $value, int $delay = 5)
    {
        $pages = $this->getSetPages($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getSet($queue, $i);

            $item = array_filter($data, function ($item) use ($field, $value) {
                $json = json_decode($item, true);
                if (empty($json['payload'][$field])) return false;

                if (is_array($json['payload'][$field])) {
                    return in_array($value, $json['payload'][$field]);
                }

                return $json['payload'][$field] == $value;
            });

            if(!empty($item)) return $item;
            sleep($delay);
        }

        return null;
    }


    /**
     * Remove item from List queue
     *
     * @param $queue
     * @param string $id
     */
    public function removeSetByID($queue, string $id, $delay = 5)
    {
        $pages = $this->getSetPages($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getSet($queue, $i);

            $item = array_filter($data, function ($item) use ($id) {
                $json = json_decode($item, true);

                return $json['id'] == $id;
            });
            if(!empty($item)) {
                $this->instance->zRem($queue, array_shift($item), 1);
                return true;
            }
            sleep($delay);
        }

        return false;
    }

}