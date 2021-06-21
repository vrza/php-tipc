<?php

namespace TIPC;

interface MessageHandler
{
    public function handleMessage(string $msg): string;
}
