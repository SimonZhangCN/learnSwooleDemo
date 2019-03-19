<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 3/12/19
 * Time: 12:02 PM
 */

$serv = new Swoole\Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

$serv->on('start', function ($server) {
    echo 'master进程ID：' . $server->master_pid . PHP_EOL;
    echo 'manager进程ID：' . $server->manager_pid . PHP_EOL;
    var_dump($server->setting);
});

$serv->on('WorkerStart', function (Swoole\Server $server, int $worker_id) {
    if ($server->taskworker) {
        echo 'taskWorker进程ID：' .$server->worker_pid . PHP_EOL;
    } else {
        echo 'worker进程ID：' .$server->worker_pid . PHP_EOL;
    }
});

$serv->start();