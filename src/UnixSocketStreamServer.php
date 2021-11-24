<?php

namespace TIPC;

class UnixSocketStreamServer
{
    const ESUCCESS = 0;
    const EINTR = 4;
    const RECV_BUF_SIZE = 64 * 1024;
    const SOCKET_BACKLOG = 4 * 1024;

    private $path;
    private $msgHandler;
    private $recvBufSize;
    private $socket;

    public function __construct(string $path, MessageHandler $msgHandler, int $recvBufSize = self::RECV_BUF_SIZE)
    {
        $this->path = $path;
        $this->msgHandler = $msgHandler;
        $this->recvBufSize = $recvBufSize;
    }

    public function __destruct()
    {
        if (is_resource($this->socket) && get_resource_type($this->socket) === 'Socket') {
            $this->close();
        }
    }

    private static function cleanUpFile(string $path): void
    {
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0750, true);
        }
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function listen(): bool
    {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if (($this->socket) === false) {
            $error = socket_last_error($this->socket);
            fwrite(STDERR, "socket_create() failed with error $error: " . socket_strerror($error) . PHP_EOL);
            return false;
        }

        self::cleanUpFile($this->path);
        $umask = umask(0117);
        $bindSuccess = socket_bind($this->socket, $this->path);
        umask($umask);
        if ($bindSuccess === false) {
            $error = socket_last_error($this->socket);
            fwrite(STDERR, "socket_bind() failed with error $error: " . socket_strerror($error) . PHP_EOL);
            return false;
        }

        if (socket_listen($this->socket, self::SOCKET_BACKLOG) === false) {
            $error = socket_last_error($this->socket);
            fwrite(STDERR, "socket_listen() failed with error $error: " . socket_strerror($error) . PHP_EOL);
            return false;
        }

        return true;
    }

    public function close(): void
    {
        socket_close($this->socket);
    }

    public function checkMessages(int $timeoutSeconds = 0, int $timeoutMicroseconds = 0): void
    {
        $limit = 1024;
        $cnt = 0;
        $sec = $timeoutSeconds;
        $usec = $timeoutMicroseconds;
        while ($this->checkMessage($sec, $usec) > 0 && $cnt < $limit) {
            $sec = $usec = 0;
            $cnt++;
        }
        if ($cnt > 0) {
            fwrite(STDERR, "server handled $cnt messages" . PHP_EOL);
        }
    }

    private function checkMessage(int $timeoutSeconds = 0, int $timeoutMicroseconds = 0): int
    {
        $read = [$this->socket];
        $write = $except = null;
        set_error_handler(function () {});
        $num = @socket_select($read, $write, $except, $timeoutSeconds, $timeoutMicroseconds);
        restore_error_handler();
        if ($num === false) {
            $error = socket_last_error();
            if ($error !== self::EINTR) {
                fwrite(STDERR, "socket_select() failed with error $error: " . socket_strerror($error) . PHP_EOL);
            }
            return -$error;
        }
        if ($num > 0) {
            foreach ($read as $socket) {
                if (($connectionSocket = socket_accept($socket)) === false) {
                    $error = socket_last_error($socket);
                    if ($error !== self::ESUCCESS) {
                        fwrite(STDERR, "socket_accept() failed with error $error: " . socket_strerror($error) . PHP_EOL);
                        return -$error;
                    }
                }
                $msg = $this->receiveMessage($connectionSocket);
                if ($msg !== null) {
                    $response = $this->msgHandler->handleMessage($msg);
                    $this->sendResponse($response, $connectionSocket);
                }
            }
        }
        return $num;
    }

    public function receiveMessage($connectionSocket)
    {
        fwrite(STDERR, '!' . PHP_EOL);
        if (($bytes = socket_recv($connectionSocket, $buf, $this->recvBufSize, 0)) === false) {
            $error = socket_last_error($this->socket);
            fwrite(STDERR, "socket_recv() failed with error $error: " . socket_strerror($error) . PHP_EOL);
            return null;
        } else {
            fwrite(STDERR, "$bytes bytes received" . PHP_EOL);
            fwrite(STDOUT, "<<<< $buf" . PHP_EOL);
            return $buf;
        }
    }

    public function sendResponse(string $response, $connectionSocket): void
    {
        $bytes = socket_send($connectionSocket, $response, strlen($response), 0);
        fwrite(STDOUT, ">>>> $response" . PHP_EOL);
        fwrite(STDERR, "$bytes bytes sent" . PHP_EOL);
    }
}
