<?php

use WillRy\RMQ\QueueSet;

require __DIR__ . "/../../vendor/autoload.php";

$rmq = new QueueSet("set", "redis", 6379);

for ($i = 0; $i < 300; $i++) {
    $rmq->publish([
        "id" => $i,
        "payload" => [
            "id" => $i,
            "name" => "Fulano",
            "descricao" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi in justo nisl. Praesent pharetra ex vel nisl sagittis ullamcorper. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur vestibulum, ipsum a vestibulum faucibus, lorem lorem semper turpis, vitae tristique est felis a urna. Cras gravida diam ac hendrerit venenatis. Vestibulum purus erat, maximus quis massa vitae, egestas euismod enim. Ut nec pulvinar nulla. Donec quis urna scelerisque, lacinia ante accumsan, fermentum eros. Aliquam sodales pulvinar quam non vehicula. Praesent odio libero, euismod at justo sed, feugiat ullamcorper orci. Cras id risus non nunc pharetra venenatis nec a leo. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae;Nunc mollis tellus odio, vel placerat nibh vehicula at. Praesent eu venenatis quam, sed tempor est. Praesent elit lectus, viverra vitae eros ac, semper posuere turpis. Proin porttitor sem nec urna consequat tempus vel sit amet magna. Aenean blandit, arcu eget accumsan porttitor, turpis mi elementum nunc, quis egestas dui risus eget magna. Sed sollicitudin mauris at dolor rhoncus, non fermentum tellus consequat. Vivamus dignissim vel quam eget pretium. Etiam vel magna aliquam, gravida erat eget, maximus orci. Pellentesque ac tempor nisl. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum nibh tortor, auctor a pellentesque nec, rutrum ac arcu.",
            "teste" => [1, 2, 3]
        ]
    ]);
}