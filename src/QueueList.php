<?php

namespace WillRy\RMQ;

use Exception;

class QueueList extends RMQ
{

    public function publish(array $payload)
    {
        if (empty($payload["tries"])) $payload["tries"] = 1;
        return $this->instance->rpush($this->queue, [json_encode($payload)]);
    }


    public function consume(Worker $workerClass, $delay = 5, $requeue = false, $max_tries = 3)
    {
        while (true) {
            $msg = $this->instance->lpop($this->queue);
            $data = !empty($msg) ? json_decode($msg, true) : null;
            try {
                $workerClass->handle($data);

            } catch (Exception $e) {
                $requeued = $this->analyzeRequeue($data, $requeue, $max_tries);

                if (!$requeued) $workerClass->error($data);
            }
            sleep($delay);
        }
    }

    /**
     * SLOW: Get item from List queue by Field
     * @param $id
     * @param int $delay
     * @return array|null
     */
    public function getByID($id, $delay = 1)
    {
        $pages = $this->getPages();
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getPaginated($i);

            $item = array_filter($data, function ($item) use ($id) {
                $json = json_decode($item, true);

                return $json['id'] == $id;
            });
            if (!empty($item)) return $item;
            sleep($delay);
        }

        return null;
    }

    public function getPages($perPage = 100)
    {
        $total = $this->getCount();
        if ($total) return ceil($total / $perPage);
        return 0;
    }

    public function getCount()
    {
        return $this->instance->llen($this->queue);
    }

    public function getPaginated($page = 1, $perPage = 100)
    {
        $page = $page < 1 ? 1 : $page;
        $offset = ($page - 1) * $perPage;
        $max = $page * $perPage;
        return $this->instance->lrange($this->queue, $offset, $max);
    }

    /**
     * SLOW: Get item from List queue by Field
     * @param string $field
     * @param string $value
     * @param int $delay
     * @return array|null
     */
    public function getByField(string $field, string $value, int $delay = 5)
    {
        $pages = $this->getPages();
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getPaginated($i);

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
     * @param string $id
     * @param int $delay
     * @return bool
     */
    public function removeByID(string $id, $delay = 5)
    {
        $pages = $this->getPages();
        for ($i = 1; $i <= $pages; $i++) {

            $data = $this->getPaginated($i);

            $item = array_filter($data, function ($item) use ($id) {
                $json = json_decode($item, true);

                return $json['id'] == $id;
            });
            if (!empty($item)) {
                $this->instance->lRem($this->queue, array_shift($item), 1);
                return true;
            }
            sleep($delay);
        }

        return false;
    }

    /**
     * Remove item from List by content
     *
     * @param string $item
     * @return bool|int
     */
    public function remove(string $item)
    {
        return $this->instance->lrem($this->queue, $item, 1);
    }
}