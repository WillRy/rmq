<?php

namespace WillRy\RMQ;


use Exception;

class QueueSet extends RMQ
{

    public function publish(array $payload)
    {

        if (empty($payload["tries"])) $payload["tries"] = 1;
        $count = $this->getCount() + $payload["id"];

        $data = $this->encode($payload);

        $this->instance
            ->transaction()
            ->hmset($this->getHashName($payload["id"]), $data)
            ->zAdd($this->queue, [json_encode($data) => $count])
            ->execute();
    }

    public function getCount()
    {
        return (int)$this->instance->zcount($this->queue, '-inf', '+inf');
    }

    public function consume(Worker $workerClass, $delay = 5, $requeue = false, $max_tries = 3)
    {
        while (true) {

            $data = null;
            $msg = $this->instance->executeRaw(["zpopmin", $this->queue, 1]);

            //no exists in set
            if (empty($msg)) continue;

            //fail to decode
            $msgJSON = $this->decode($msg);

            if (empty($msgJSON)) continue;

            //fail to get data
            $data = $this->getByID($msgJSON["id"]);
            if (empty($data)) continue;

            try {
                //delete item from queue
                $this->removeByID($data["id"]);

                $workerClass->handle($data);

            } catch (Exception $e) {
                $requeued = $this->analyzeRequeue($data, $requeue, $max_tries);

                try {
                    if (!$requeued) $workerClass->error($data);
                } catch (Exception $e) {

                }
            }
            sleep($delay);
        }
    }

    public function getByID($id)
    {
        return $this->instance->hgetall($this->getHashName($id));
    }

    public function removeByID(string $id)
    {
        $arrayhash = $this->getByID($id);


        if (!$arrayhash) return true;

        $data = $this->encode($arrayhash);
        if (!$data) return $this->instance->del($this->getHashName($id));

        $this->instance->transaction()->del($this->getHashName($id))->zrem($this->queue, $data)->execute();
        return true;
    }

    public function getSearch($value, $count = 10)
    {
        $searchResults = [];

        $results = $this->instance->zscan($this->queue, 0, [
            'MATCH' => "*$value*",
            'COUNT' => $count
        ]);
        if (!$results) return [];
        foreach ($results as $result) {
            if (!is_array($result)) continue;

            foreach ($result as $key => $item) {
                $searchResults[] = json_decode($key, true);
            }

        }

        return $searchResults;
    }

    public function removeOldHash()
    {

        $keys = $this->instance->scan(0, [
            "MATCH" => "queue:{$this->queueName}:*",
            "COUNT" => 9999999
        ]);

        $keys = array_filter($keys, function ($item) {
            return is_array($item);
        }, ARRAY_FILTER_USE_BOTH);

        $values = [];
        foreach ($keys as $key) {
            if (is_array($keys)) $values = array_values($key);
        }


        if (empty($values)) return;

        foreach ($values as $key) {
            $pages = $this->getPages();

            $segments = explode(":", $key);
            if (count($segments) < 2) {
                continue;
            }

            $id = $segments[2];

            if (empty($pages)) {
                var_dump($key);
                $this->removeByID($id);
                continue;
            }


            for ($i = 1; $i <= $pages; $i++) {

                $data = $this->getPaginated($i, 500);

                $item = array_filter($data, function ($item) use ($id) {

                    $json = json_decode($item, true);

                    return $json['id'] == $id;
                });


                if (empty($item)) $this->removeByID($id);

                sleep(1);

            }
        }
        return null;
    }

    public function getPages($perPage = 10)
    {
        $total = $this->getCount();
        if ($total) return ceil($total / $perPage);
        return 0;
    }

    public function getPaginated($page = 1, $perPage = 10)
    {
        $page = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $perPage;
        $max = $page * $perPage;
        return $this->instance->zrange($this->queue, $offset, $max);
    }
}