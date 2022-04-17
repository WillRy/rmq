<?php

require __DIR__."/../../vendor/autoload.php";


$rmq = new \WillRy\RMQ\RMQ("redis", 6379);

/** List pages */
$pages = $rmq->getListPages("queue_list");

/** List paginated */
$page = 1;
$perPage = 10;
$all = $rmq->getList("queue_list", 1, 10);

/** Get list item by id */
$byID = $rmq->getListByID("queue_list", 200, 1);

/** Get list item by field */
$byField = $rmq->getListByField("queue_list", "id", 200, 1);

/** Remove item by ID */
$removed = $rmq->removeListByID("queue_list", 200);



var_dump([
    "byID" => $byID,
    "byField" => $byField,
    "removed" => $removed
]);

