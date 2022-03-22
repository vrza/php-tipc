<?php

namespace TIPC;

class InetSocketBinder extends SocketBinder
{
    public static function bind(&$socket, SocketAddress $address): bool
    {
        return socket_bind($socket, $address->getAddress(), $address->getPort());
    }
}
