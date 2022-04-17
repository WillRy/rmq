<?php

require __DIR__."/../../vendor/autoload.php";


$rmq = new \WillRy\RMQ\RMQ("redis", 6379);

/** List pages */
$pages = $rmq->getSetPages("set_list");

/** List paginated */
$page = 1;
$perPage = 10;
$all = $rmq->getSet("set_list", 1, 10);

/** Get list item by id */
$byID = $rmq->getSetByID("set_list", 200, 1);

/** Get list item by field */
$byField = $rmq->getSetByField("set_list", "id", 200, 1);

/** Remove item by ID */
$removed = $rmq->removeSetByID("set_list", 200);



var_dump([
    "byID" => $byID,
    "byField" => $byField,
    "removed" => $removed
]);

