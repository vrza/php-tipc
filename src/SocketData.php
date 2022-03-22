<?php

namespace TIPC;

/**
 * Data class that holds a tuple of
 * socket address and
 * handler for message coming in over that socket
 */
class SocketData
{
    private $address;
    private $handler;

    public function __construct(SocketAddress $address, MessageHandler $handler)
    {
        $this->address = $address;
        $this->handler = $handler;
    }

    /**
     * @return string
     */
    public function getAddress(): SocketAddress
    {
        return $this->address;
    }

    /**
     * @return MessageHandler
     */
    public function getHandler(): MessageHandler
    {
        return $this->handler;
    }

}