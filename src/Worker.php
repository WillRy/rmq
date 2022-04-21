<?php

namespace WillRy\RMQ;

interface Worker
{
    public function handle(array $data);
    public function error($data);
}