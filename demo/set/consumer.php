<?php

use WillRy\RMQ\QueueSet;

require __DIR__ . "/../../vendor/autoload.php";

require __DIR__ . "/../WorkerTest.php";

$rmq = new QueueSet("set", "redis", 6379);

$worker = new WorkerTest();

/** Queue work */

$rmq->consume($worker, 1, true, 3);

