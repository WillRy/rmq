<?php

namespace WillRy\RMQ;


use Predis\Collection\Iterator;

class QueueSet extends RMQ
{

    public function publish(string $queue, array $payload)
    {
        if (empty($payload["tries"])) $payload["tries"] = 1;
        $count = $this->getCount($queue) + $payload["id"];

        $data = $this->encode($payload);
        $this->instance
            ->transaction()
            ->hmset($payload["id"], $data)
            ->zAdd($queue, [json_encode($data) => $count])
            ->execute();
    }

    public function consume(Worker $workerClass, string $queue, $delay = 5, $requeue = false, $max_tries = 3)
    {
        while (true) {
            $data = null;
            $msg = $this->instance->executeRaw(["zpopmin", $queue, 1]);
            $data = $this->decode($msg);
            if (!empty($data)) $this->removeByID($queue, $data["id"]);

            try {
                if ($data) $workerClass->handle($data);

            } catch (\Exception $e) {
                $requeued = $this->analyzeRequeue($data, $queue, $requeue, $max_tries, true);

                try {
                    if (!$requeued) $workerClass->error($data);
                } catch (\Exception $e) {

                }
            }
            sleep($delay);
        }
    }

    public function getPaginated($queue, $page = 1, $perPage = 10)
    {
        $page = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $perPage;
        $max = $page * $perPage;
        return $this->instance->zrange($queue, $offset, $max);
    }

    public function getSearch($queue, $value, $count = 10)
    {
        $searchResults = [];

        $results = $this->instance->zscan($queue, 0, [
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

    public function getCount($queue)
    {
        return $this->instance->zcount($queue, '-inf', '+inf');
    }

    public function getPages($queue, $perPage = 10)
    {
        $total = $this->getCount($queue);
        if ($total) return ceil($total / $perPage);
        return 0;
    }

    public function getByID($id)
    {
        return $this->instance->hgetall($id);
    }

    public function removeByID($queue, string $id)
    {
        $arrayhash = $this->getByID($id);
        if (!$arrayhash) return true;

        $data = $this->encode($arrayhash);
        if (!$data) return $this->instance->del($id);

        $this->instance->transaction()->del($id)->zrem($queue, $data)->execute();
        return true;
    }

    public function removeOldHash($queue)
    {
        foreach (new Iterator\Keyspace($this->instance, "*") as $key) {
            $pages = $this->getPages($queue);

            if (empty($pages)) return $this->removeByID($queue, $key);


            for ($i = 1; $i <= $pages; $i++) {

                $data = $this->getPaginated($queue, $i, 500);

                $item = array_filter($data, function ($item) use ($key) {

                    $json = json_decode($item, true);

                    return $json['id'] == $key;
                });


                if (empty($item)) $this->removeByID($queue, $key);

                sleep(1);

            }
        }
        return null;
    }
}