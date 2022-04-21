<?php

namespace WillRy\RMQ;

class QueueList extends RMQ
{

    public function publish(string $queue, array $payload)
    {
        if (empty($payload["tries"])) $payload["tries"] = 1;
        return $this->instance->rpush($queue, [json_encode($payload)]);
    }


    public function consume(Worker $workerClass, string $queue, $delay = 5, $requeue = false, $max_tries = 3)
    {
        while (true) {
            $msg = $this->instance->lpop($queue);
            $data = !empty($msg) ? json_decode($msg, true) : null;
            try {
                if (!empty($data)) $workerClass->handle($data);

            } catch (\Exception $e) {
                $requeued = $this->analyzeRequeue($data, $queue, $requeue, $max_tries);

                if (!$requeued) $workerClass->error($data);
            }
            sleep($delay);
        }
    }


    public function getPaginated($queue, $page = 1, $perPage = 100)
    {
        $page = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $perPage;
        $max = $page * $perPage;
        return $this->instance->lrange($queue, $offset, $max);
    }

    public function getCount($queue)
    {
        return $this->instance->llen($queue);
    }

    public function getPages($queue, $perPage = 100)
    {
        $total = $this->getCount($queue);
        if ($total) return ceil($total / $perPage);
        return 0;
    }

    /**
     * SLOW: Get item from List queue by Field
     * @param string $queue
     * @param $id
     * @param int $delay
     * @return array|null
     */
    public function getByID($queue, $id, $delay = 1)
    {
        $pages = $this->getPages($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getPaginated($queue, $i);

            $item = array_filter($data, function ($item) use ($id) {
                $json = json_decode($item, true);

                return $json['id'] == $id;
            });
            if (!empty($item)) return $item;
            sleep($delay);
        }

        return null;
    }

    /**
     * SLOW: Get item from List queue by Field
     * @param string $queue
     * @param string $field
     * @param string $value
     * @param int $delay
     * @return array|null
     */
    public function getByField(string $queue, string $field, string $value, int $delay = 5)
    {
        $pages = $this->getPages($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getPaginated($queue, $i);

            $item = array_filter($data, function ($item) use ($field, $value) {
                $json = json_decode($item, true);
                if (empty($json['payload'][$field])) return false;

                if (is_array($json['payload'][$field])) {
                    return in_array($value, $json['payload'][$field]);
                }

                return $json['payload'][$field] == $value;
            });

            if (!empty($item)) return $item;
            sleep($delay);
        }

        return null;
    }


    /**
     * SLOW: Remove item from List queue by ID
     *
     * @param $queue
     * @param string $id
     */
    public function removeByID($queue, string $id, $delay = 5)
    {
        $pages = $this->getPages($queue);
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getPaginated($queue, $i);

            $item = array_filter($data, function ($item) use ($id) {
                $json = json_decode($item, true);

                return $json['id'] == $id;
            });
            if (!empty($item)) {
                $this->instance->lRem($queue, array_shift($item), 1);
                return true;
            }
            sleep($delay);
        }

        return false;
    }

    /**
     * Remove item from List by content
     *
     * @param $queue
     * @param string $item
     * @return bool|int
     */
    public function remove($queue, string $item)
    {
       return $this->instance->lrem($queue, $item, 1);
    }
}