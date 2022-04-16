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
     * @var Client
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

    public static function getInstance(): ?Client
    {
        if (empty(self::$instance)) {
            self::$instance = new Client([
                'scheme' => self::$opt["scheme"],
                'host' => self::$opt["host"],
                'port' => self::$opt["port"],
                'persistent' => self::$opt["persistent"],
            ]);
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