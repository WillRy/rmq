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

### Publicador

```php
<?php

require __DIR__."/vendor/autoload.php";

$rmq = new \WillRy\RMQ\RMQ();

$rmq->publish("fila", [
        "id" => rand(),
        "payload" => [
            "id" => rand(),
            "name" => "Fulano"
        ]
]);

```

### Consumidor

**Classe de consumidor**

A classe do consumidor deve implementar a interface **Worker**.

A função **handle** executa o processamento e a função **error** é
chamada caso o item tenha tido erro mesmo após as retentativas.

```php
<?php

class WorkerTest implements \WillRy\RMQ\Worker
{

    public function handle(array $data = [])
    {
        try {
            /** Erro fake para simular o mecanismo de retentativa */
            if (rand() % 2 === 0) throw new \Exception("Erro");

            $json = json_encode($data);

            print("Success: $json" . PHP_EOL);
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

require __DIR__."/vendor/autoload.php";

require __DIR__."/WorkerTest.php";

$rmq = new \WillRy\RMQ\RMQ("redis", 6379);

$worker = new WorkerTest();

/** Queue work */
$rmq->consumeWorker($worker, "fila", 1, true, 3);

```