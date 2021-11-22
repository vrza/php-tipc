#!/usr/bin/env php
<?php

error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use TIPC\UnixSocketStreamServer;
use TIPC\Test\MockMessageHandler;

const TICK_TIME = 1;
const EXIT_SOCKET = 72;

$file = '/run/user/' . posix_geteuid() . '/tipc/socket';
$msgHandler = new MockMessageHandler();
$server = new UnixSocketStreamServer($file, $msgHandler);
if ($server->listen() === false) {
    fwrite(STDERR, "Fatal error: could not listen on socket $file");
    exit(EXIT_SOCKET);
}
while (true) {
    fwrite(STDERR, '.');
    $server->checkMessages(TICK_TIME);
}
