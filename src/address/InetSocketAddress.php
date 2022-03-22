<?php

namespace TIPC;

class InetSocketAddress extends SocketAddress
{
    private $address;
    private $port;
    private $domain;

    public function __construct(string $address, int $port, int $domain = AF_INET)
    {
        $this->address = $address;
        $this->port = $port;
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return int
     */
    public function getDomain(): int
    {
        return $this->domain;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->getAddress()}:{$this->getPort()}";
    }
}
