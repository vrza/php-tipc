<?php

namespace TIPC\Test;

use TIPC\MessageHandler;

class MockMessageHandler3 implements MessageHandler
{
    public function handleMessage(string $msg): string
    {
        return "Yet another handler handling message $msg";
    }
}
