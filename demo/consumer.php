<?php

require __DIR__."/../vendor/autoload.php";

require __DIR__."/WorkerTest.php";

$rmq = new WorkerTest("redis", 6379);

$rmq->consumeWorker("fila", 5, true, 3);