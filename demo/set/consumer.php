<?php

require __DIR__."/../../vendor/autoload.php";

require __DIR__."/../WorkerTest.php";

$rmq = new \WillRy\RMQ\QueueSet("redis", 6379);

$worker = new WorkerTest();

/** Queue work */

$rmq->consume($worker, "queue_set", 1, true, 3);

