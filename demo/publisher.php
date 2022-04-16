<?php

require __DIR__."/../vendor/autoload.php";

$rmq = new \WillRy\RMQ\RMQ("redis", 6379);

for ($i = 0; $i < 30; $i++) {
    $rmq->publish("fila", [
        "id" => $i,
        "payload" => [
            "name" => "Fulano"
        ]
    ]);
}