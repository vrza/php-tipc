<?php

namespace TIPC;

abstract class SocketAddress
{
    /**
     * @return string
     */
    abstract public function getAddress(): string;

    /**
     * @return int
     */
    abstract public function getDomain(): int;

    /**
     * @return int
     */
    abstract public function getPort(): int;
}
