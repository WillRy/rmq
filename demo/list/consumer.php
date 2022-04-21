<?php

require __DIR__."/../../vendor/autoload.php";

require __DIR__."/../WorkerTest.php";

$rmq = new \WillRy\RMQ\QueueList("redis", 6379);

$worker = new WorkerTest();

/** Queue work */
$rmq->consume($worker, "queue_list", 1, true, 3);
