<?php

namespace TIPC;

abstract class SocketBinder
{
    abstract public static function bind(&$socket, SocketAddress $address): bool;

    static public function get(SocketAddress $address): string
    {
        return $address instanceof UnixDomainSocketAddress
            ? UnixDomainSocketBinder::class
            : InetSocketBinder::class;
    }
}
