<?php

namespace TIPC;

class UnixDomainSocketBinder extends SocketBinder
{
    public static function bind(&$socket, SocketAddress $address): bool
    {
        static::cleanUpFile($address->getAddress());
        $umask = umask(0117);
        $bindSuccess = socket_bind($socket, $address->getAddress());
        umask($umask);
        return $bindSuccess;
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
}
