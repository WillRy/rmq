<?php

require __DIR__."/../../vendor/autoload.php";


$rmq = new \WillRy\RMQ\QueueList("redis", 6379);

/** List pages */
$pages = $rmq->getPages("queue_list");

/** List paginated */
$page = 1;
$perPage = 10;
$all = $rmq->getPaginated("queue_list", 1, 10);

/** Get list item by id */
$byID = $rmq->getByID("queue_list", 1, 1);

/** Get list item by field */
$byField = $rmq->getByField("queue_list", "id", 1, 1);

/** Remove item by ID */
$removed = $rmq->removeByID("queue_list", 1);



var_dump([
    "byID" => $byID,
    "byField" => $byField,
    "removed" => $removed
]);

