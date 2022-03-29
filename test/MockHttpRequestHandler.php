<?php

namespace TIPC\Test;

use TIPC\MessageHandler;

class MockHttpRequestHandler implements MessageHandler
{
    public function handleMessage(string $msg): string
    {
        return self::controller();
    }

    private static function controller(): string
    {
        $protocol = 'HTTP/1.1';
        $status = '200 OK';
        $server = 'php-tipc-httpd';
        $date = self::currentDate();
        $content = '<html>
    <title>php-tipc</title>
    <body>
        <h1>Hello from php-tipc!</h1>
    </body>
</html>';
        $contentType = 'text/html';
        $contentLength = strlen($content);
        $head =
            "{$protocol} {$status}" . PHP_EOL .
            "Server: {$server}" . PHP_EOL .
            "Date: {$date}" . PHP_EOL .
            "Content-Type: {$contentType}" . PHP_EOL .
            "Content-Length: {$contentLength}" . PHP_EOL .
            "Last-Modified: {$date}" . PHP_EOL;
        return $head . PHP_EOL . $content;
    }

    private static function currentDate(): string
    {
        return gmdate('D, d M Y H:i:s T');
    }
}
