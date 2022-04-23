<?php

use WillRy\RMQ\QueueList;

require __DIR__ . "/../../vendor/autoload.php";


$rmq = new QueueList("redis", 6379);

/** List pages */
$pages = $rmq->getPages();

/** List paginated */
$page = 1;
$perPage = 10;
$all = $rmq->getPaginated(1, 10);

/** Get list item by id */
//$byID = $rmq->getByID(1, 1);

/** Get list item by field */
//$byField = $rmq->getByField("id", 1, 1);

/** Remove item by ID */
$removed = $rmq->removeByID(1);


var_dump([
    "all" => $all,
    "removed" => $removed
]);

