<?php

$client = new swoole_client(SWOOLE_SOCK_TCP);
if (!$client->connect('192.168.220.144', 9501, -1))
{
    exit("connect failed. Error: {$client->errCode}\n");
}
$client->send("hello world\n");
$client->close();