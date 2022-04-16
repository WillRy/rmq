<?php

require __DIR__."/../vendor/autoload.php";

require __DIR__."/WorkerTest.php";

$rmq = new \WillRy\RMQ\RMQ("redis", 6379);

$worker = new WorkerTest();

/** Queue work */
$rmq->consumeWorker($worker, "fila", 1, true, 3);

/** Delete the queue */
//$rmq->excludeQueue("fila");