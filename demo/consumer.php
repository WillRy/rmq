<?php

require __DIR__."/../vendor/autoload.php";

require __DIR__."/WorkerTest.php";

$rmq = new WorkerTest("redis", 6379);

/** Queue work */
$rmq->consumeWorker("fila", 1, true, 3);

/** Delete the queue */
//$rmq->excludeQueue("fila");