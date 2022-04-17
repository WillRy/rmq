<?php

require __DIR__."/../../vendor/autoload.php";

$rmq = new \WillRy\RMQ\RMQ("redis", 6379);

for ($i = 0; $i < 300000; $i++) {
    $rmq->publishList("queue_list", [
        "id" => $i,
        "payload" => [
            "id" => $i,
            "name" => "Fulano"
        ]
    ]);
}