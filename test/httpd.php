#!/usr/bin/env php
<?php

error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use TIPC\InetSocketAddress;
use TIPC\SocketData;
use TIPC\SocketStreamsServer;
use TIPC\Test\MockHttpRequestHandler;

const EXIT_SUCCESS = 0;
const EXIT_SOCKET = 72;
const TICK_TIME = 1;

$handler = new MockHttpRequestHandler();
$address = new InetSocketAddress("127.0.0.1", 8080);
$server = new SocketStreamsServer([new SocketData($address, $handler)]);

$shutdown = function (int $signo, $siginfo) use ($server) {
    fwrite(STDERR, "Caught signal $signo" . PHP_EOL);
    $server->closeAll();
    exit(EXIT_SUCCESS);
};

pcntl_signal(SIGINT, $shutdown);
pcntl_signal(SIGTERM, $shutdown);

if ($server->listen() === false) {
    fwrite(STDERR, "Fatal error: could not listen on $address" . PHP_EOL);
    exit(EXIT_SOCKET);
}
fwrite(STDOUT, "==> Listening on $address" . PHP_EOL);

while (true) {
    fwrite(STDERR, '.');
    $server->checkMessages(TICK_TIME);
    pcntl_signal_dispatch();
}
