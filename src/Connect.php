<?php

namespace Willry\RMQ;

use Predis\Client;

/**
 * Class Connect Singleton Pattern
 */
class Connect
{
    /**
     * @const array
     */
    private static $opt = [];

    /**
     * @var Redis
     */
    private static $instance;

    /**
     * Connect constructor. Private singleton
     */
    private function __construct()
    {
    }

    /**
     * Connect clone. Private singleton
     */
    private function __clone()
    {
    }

    public static function getInstance(): ?\Redis
    {
        if (empty(self::$instance)) {
            $redis = new \Redis();
            $redis->pconnect(
                self::$opt["host"],
                self::$opt["port"]
            );
            self::$instance = $redis;
        }

        return self::$instance;
    }

    public static function config($host, $port = 6379, $persistent = "1")
    {
        self::$opt = [
            'scheme' => "tcp",
            'host' => $host,
            'port' => $port,
            'persistent' => $persistent,
        ];
    }
}