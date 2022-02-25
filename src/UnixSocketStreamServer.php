<?php

namespace TIPC;

class UnixSocketStreamServer
{
    const ESUCCESS = 0;
    const EINTR = 4;
    const RECV_BUF_SIZE = 64 * 1024;
    const SOCKET_BACKLOG = 4 * 1024;

    private $msgHandler;
    private $path;
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

    public function checkMessages(int $timeoutSeconds = 0, int $timeoutMicroseconds = 0): int
    {
        $limit = 1024;
        $cnt = 0;
        $sec = $timeoutSeconds;
        $usec = $timeoutMicroseconds;
        while ($this->checkMessage($sec, $usec) > 0 && $cnt++ < $limit) {
            $sec = $usec = 0;
        }
        if ($cnt > 0) {
            fwrite(STDERR, "server handled $cnt messages" . PHP_EOL);
        }
        return $cnt;
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
                $this->handleConnection($connectionSocket);
            }
        }
        return $num;
    }

    private function handleConnection($connectionSocket)
    {
        $msg = $this->receiveMessage($connectionSocket);
        if ($msg !== null) {
            $response = $this->msgHandler->handleMessage($msg);
            $this->sendResponse($response, $connectionSocket);
        }
        socket_close($connectionSocket);
    }

    private function receiveMessage($connectionSocket)
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

    private function sendResponse(string $response, $connectionSocket): void
    {
        $bytes = socket_send($connectionSocket, $response, strlen($response), 0);
        fwrite(STDOUT, ">>>> $response" . PHP_EOL);
        fwrite(STDERR, "$bytes bytes sent" . PHP_EOL);
    }

    /**
     * Given a file name and a list of candidate directories, find a path
     * where a Unix socket file can be created by the server.
     *
     * If a directory from the list does not exist an attempt will be made
     * to create it.
     *
     * Returns the path to the Unix socket file,
     * or null if a socket file can not be created.
     *
     * @param string $socketFileName
     * @param array $socketDirs
     * @return string|null
     */
    public static function findSocketPath(string $socketFileName, array $socketDirs): ?string
    {
        if (is_null($socketDir = self::ensureWritableDir($socketDirs))) {
            fwrite(STDERR, "Could not find a writable directory for Unix domain socket: $socketFileName" . PHP_EOL);
            fwrite(STDERR, "Ensure one of these is writable: " . implode(', ', $socketDirs) . PHP_EOL);
            return null;
        }
        return $socketDir . '/' . $socketFileName;
    }

    /**
     * Try to find a writable directory from a list of candidates,
     * possibly creating a new directory if possible.
     *
     * We are intentionally suppressing errors when attempting to create
     * directories, regardless of the reason (file exists,
     * insufficient permissions...), as this is not a critical failure.
     *
     * Returns path to a writeable directory, or false if a writeable
     * directory is not available.
     *
     * @param array $candidateDirs
     * @return string|false
     */
    private static function ensureWritableDir(array $candidateDirs): ?string
    {
        foreach ($candidateDirs as $candidateDir) {
            if (!file_exists($candidateDir)) {
                set_error_handler(function () {});
                @mkdir($candidateDir, 0700, true);
                restore_error_handler();
            }
            if (is_dir($candidateDir) && is_writable($candidateDir)) {
                return $candidateDir;
            }
        }
        return null;
    }
}
