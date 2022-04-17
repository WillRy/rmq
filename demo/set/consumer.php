<?php

require __DIR__."/../../vendor/autoload.php";

require __DIR__."/WorkerTest.php";

$rmq = new \WillRy\RMQ\RMQ("redis", 6379);

$worker = new WorkerTest();

/** Queue work */
$rmq->consumeOrderedSet($worker, "set_list", 1, true, 3);
