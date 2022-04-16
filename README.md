# Redis Message Queue

Um gerenciador de filas com Redis, permitindo o uso de workers e número de tentativas de processamento em caso de erro

## Como utilizar?

O código de exemplo está na pasta **demo**

### Publicador

```php
<?php

require __DIR__."/../vendor/autoload.php";

$rmq = new \WillRy\RMQ\RMQ();

$rmq->publish("fila", [
        "id" => rand(),
        "payload" => [
            "name" => "Fulano"
        ]
]);

```

### Consumidor

**Classe de consumidor**

A classe do consumidor deve extender da **RMQ** e implementar
a interface **Worker**.

A função **handle** executa o processamento e a função **error** é
chamada caso o item tenha tido erro mesmo após as retentativas.

```php
<?php

class WorkerTest extends \WillRy\RMQ\RMQ implements \WillRy\RMQ\Worker
{
    public function handle(array $data = [])
    {
        try {
            /** Erro fake para simular o mecanismo de retentativa */
            if (rand() % 2 === 0) throw new \Exception("Erro");

            $json = json_encode($data);

            print("Success: {$json}" . PHP_EOL);
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

**Script que consome**

```php
<?php

require __DIR__."/../vendor/autoload.php";

require __DIR__."/WorkerTest.php";

$rmq = new WorkerTest("redis", 6379);

/** Queue work */
$rmq->consumeWorker("fila", 1, true, 3);

/** Delete the queue */
//$rmq->excludeQueue("fila");
```