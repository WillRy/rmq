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