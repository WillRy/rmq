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

    /** @var Client */
    private static $instanceRedis;

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

    public static function getInstancePredis(): ?Client
    {
        if (empty(self::$instanceRedis)) {
            $redis = new Client([
                'host' => self::$opt["host"],
                'port' => self::$opt["port"],
                'persistent' => 1
            ]);

            self::$instanceRedis = $redis;
        }
        return self::$instanceRedis;
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