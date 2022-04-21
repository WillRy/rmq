# Redis Message Queue

Um gerenciador de filas com Redis, permitindo o uso de workers e número de tentativas de processamento em caso de erro

O gerenciador de filas permite utilizar as filas com dois tipos de dados:

- List
- Set

O List tem se mostrado **mais performático** para a **"entrada"** e **"saída"**
de itens, mas **lento** para **pesquisas** e **remoção**

O Set tem se mostrado **menos performático** para a **"entrada"** e **"saída"**
de itens, mas **rapido** para **pesquisas** e **remoção**


## Como utilizar?

**Instalar via composer**

```shell
composer require willry/rmq
```

Na pasta **demo** é possível ver exemplos de uso de **list** e **set**

Tendo os arquivos:

- Consumo: consumer.php
- Publicar: publisher.php
- Manipular: manipulate.php


## Performance

É possível ter mais de 300.000 itens na fila, com pouco uso de CPU e vários workers

## Utilização de fila

É preciso criar uma classe responsável pelo processamento, implementando a interface **\WillRy\RMQ\Worker**

```php
<?php

class WorkerTest implements \WillRy\RMQ\Worker
{

    public function handle(array $data = [])
    {
        try {
            /** Erro fake para simular o mecanismo de retentativa */
            if(rand() % 2 === 0) throw new \Exception("Erro");

            print("Success: {$data['id']}" . PHP_EOL);
        } catch (\Exception $e) {
            print("Retrying: {$data["id"]}" . PHP_EOL);
            throw $e;
        }

    }

    public function error($data)
    {
        print("Error: {$data["id"]}" . PHP_EOL);
    }
}
```

## Utilização de fila com Lista

### Publicar

```php
<?php

require __DIR__."/../../vendor/autoload.php";

$rmq = new \WillRy\RMQ\QueueList("redis", 6379);

for ($i = 0; $i < 300000; $i++) {
    $rmq->publish("queue_list", [
        "id" => $i,
        "payload" => [
            "id" => $i,
            "name" => "Fulano",
            "descricao" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi in justo nisl. Praesent pharetra ex vel nisl sagittis ullamcorper. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur vestibulum, ipsum a vestibulum faucibus, lorem lorem semper turpis, vitae tristique est felis a urna. Cras gravida diam ac hendrerit venenatis. Vestibulum purus erat, maximus quis massa vitae, egestas euismod enim. Ut nec pulvinar nulla. Donec quis urna scelerisque, lacinia ante accumsan, fermentum eros. Aliquam sodales pulvinar quam non vehicula. Praesent odio libero, euismod at justo sed, feugiat ullamcorper orci. Cras id risus non nunc pharetra venenatis nec a leo. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae;Nunc mollis tellus odio, vel placerat nibh vehicula at. Praesent eu venenatis quam, sed tempor est. Praesent elit lectus, viverra vitae eros ac, semper posuere turpis. Proin porttitor sem nec urna consequat tempus vel sit amet magna. Aenean blandit, arcu eget accumsan porttitor, turpis mi elementum nunc, quis egestas dui risus eget magna. Sed sollicitudin mauris at dolor rhoncus, non fermentum tellus consequat. Vivamus dignissim vel quam eget pretium. Etiam vel magna aliquam, gravida erat eget, maximus orci. Pellentesque ac tempor nisl. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum nibh tortor, auctor a pellentesque nec, rutrum ac arcu."
        ]
    ]);
}
```

### Consumir

```php
<?php

require __DIR__."/../../vendor/autoload.php";

require __DIR__."/../WorkerTest.php";

$rmq = new \WillRy\RMQ\QueueList("redis", 6379);

$worker = new WorkerTest();

/** Queue work */
$rmq->consume($worker, "queue_list", 1, true, 3);
```


## Utilização de fila com Set Ordenado

### Publicar

```php
<?php

require __DIR__."/../../vendor/autoload.php";

$rmq = new \WillRy\RMQ\QueueSet("redis", 6379);

for ($i = 0; $i < 300000; $i++) {
    $rmq->publish("queue_set", [
        "id" => $i,
        "payload" => [
            "id" => $i,
            "name" => "Fulano",
            "descricao" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi in justo nisl. Praesent pharetra ex vel nisl sagittis ullamcorper. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur vestibulum, ipsum a vestibulum faucibus, lorem lorem semper turpis, vitae tristique est felis a urna. Cras gravida diam ac hendrerit venenatis. Vestibulum purus erat, maximus quis massa vitae, egestas euismod enim. Ut nec pulvinar nulla. Donec quis urna scelerisque, lacinia ante accumsan, fermentum eros. Aliquam sodales pulvinar quam non vehicula. Praesent odio libero, euismod at justo sed, feugiat ullamcorper orci. Cras id risus non nunc pharetra venenatis nec a leo. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae;Nunc mollis tellus odio, vel placerat nibh vehicula at. Praesent eu venenatis quam, sed tempor est. Praesent elit lectus, viverra vitae eros ac, semper posuere turpis. Proin porttitor sem nec urna consequat tempus vel sit amet magna. Aenean blandit, arcu eget accumsan porttitor, turpis mi elementum nunc, quis egestas dui risus eget magna. Sed sollicitudin mauris at dolor rhoncus, non fermentum tellus consequat. Vivamus dignissim vel quam eget pretium. Etiam vel magna aliquam, gravida erat eget, maximus orci. Pellentesque ac tempor nisl. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum nibh tortor, auctor a pellentesque nec, rutrum ac arcu.",
            "teste" => [1,2,3]
        ]
    ]);
}
```

### Consumir

```php
<?php

require __DIR__."/../../vendor/autoload.php";

require __DIR__."/../WorkerTest.php";

$rmq = new \WillRy\RMQ\QueueSet("redis", 6379);

$worker = new WorkerTest();

/** Queue work */

$rmq->consume($worker, "queue_set", 1, true, 50);
```