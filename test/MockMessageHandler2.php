<?php

namespace TIPC\Test;

use TIPC\MessageHandler;

class MockMessageHandler2 implements MessageHandler
{
    public function handleMessage(string $msg): string
    {
        return "Another handler acknowledging request for $msg";
    }
}
