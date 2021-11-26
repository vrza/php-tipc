<?php

namespace TIPC;

class UnixSocketStreamClient
{
    const RECV_BUF_SIZE = 64 * 1024;

    private $path;
    private $recvBufSize;
    private $socket;
    public $verbose = 1;

    public function __construct(string $path, int $recvBufSize = self::RECV_BUF_SIZE)
    {
        $this->path = $path;
        $this->recvBufSize = $recvBufSize;
    }

    public function __destruct()
    {
        if (is_resource($this->socket) && get_resource_type($this->socket) === 'Socket') {
            $this->disconnect();
        }
    }

    public function connect(): bool
    {
        if (($this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0)) === false) {
            if ($this->verbose) fwrite(
                STDERR,
                "socket_create() failed" . PHP_EOL
            );
        }
        if ($this->verbose > 1) fwrite(STDERR, "Attempting to connect to $this->path... ");
        if (($result = @socket_connect($this->socket, $this->path)) === false) {
            if ($this->verbose) fwrite(
                STDERR,
                "socket_connect() failed: " .
                socket_strerror(socket_last_error($this->socket)) . PHP_EOL
            );
        } else {
            if ($this->verbose > 1) fwrite(STDERR, "connected." . PHP_EOL);
        }
        return $result;
    }

    public function disconnect(): void
    {
        socket_close($this->socket);
    }

    public function sendMessage(string $msg)
    {
        if (($bytes = @socket_send($this->socket, $msg, strlen($msg), 0)) === false) {
            if ($this->verbose) fwrite(
                STDERR,
                "socket_send() failed: " .
                socket_strerror(socket_last_error($this->socket)) . PHP_EOL
            );
        } else {
            if ($this->verbose > 1) fwrite(STDOUT, ">>>> $msg" . PHP_EOL);
            if ($this->verbose > 1) fwrite(STDERR, "$bytes bytes sent" . PHP_EOL);
        }
        return $bytes;
    }

    public function receiveMessage()
    {
        if (($bytes = @socket_recv($this->socket, $buf, $this->recvBufSize, 0)) === false) {
            if ($this->verbose) fwrite(
                STDERR,
                "socket_recv() failed: " .
                socket_strerror(socket_last_error($this->socket)) . PHP_EOL
            );
            return null;
        } else {
            if ($this->verbose > 1) fwrite(STDERR, "$bytes bytes received" . PHP_EOL);
            return $buf;
        }
    }

}
