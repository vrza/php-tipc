#!/usr/bin/env php
<?php

error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use TIPC\InetSocketAddress;
use TIPC\SocketStreamClient;

const EXIT_USAGE = 64;
const EXIT_NO_CONNECTION = 69;

//$address = new InetSocketAddress('127.0.0.1', 1414);
$address = new InetSocketAddress('::1', 1616, AF_INET6);

if ($argc < 2) {
    fwrite(STDERR, "Usage: ${argv[0]} <command>" . PHP_EOL);
    exit(EXIT_USAGE);
}
$msg = $argv[1];

$client = new SocketStreamClient($address);
if ($client->connect() === false) {
    exit(EXIT_NO_CONNECTION);
}
$client->sendMessage($msg);
$response = $client->receiveMessage();
fwrite(STDOUT, $response . PHP_EOL);
$client->disconnect();
