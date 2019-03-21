<?php

$serv = new Swoole\Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);


$serv->set([
    'worker_num' => 2,
    'task_worker_num' => 1,
    'reload_async' => true
]);

// 下面所有事件回调的详细介绍请查看官方文档，以更加全面了解
$serv->on('start', function (Swoole\Server $server) {
    echo '触发了Start事件回调' . PHP_EOL;
    echo 'master进程ID：' . $server->master_pid . PHP_EOL;
    echo 'manager进程ID：' . $server->manager_pid . PHP_EOL;
});
// 整个服务停止时触发的回调
$serv->on('ShutDown', function (Swoole\Server $server) {
    echo '触发了ShutDown事件回调' . PHP_EOL;
});

// manager进程启动时触发的回调
$serv->on('ManagerStart', function (Swoole\Server $server) {
    echo '触发了ManagerStart事件回调' . PHP_EOL;
});
// manager进程结束时触发的回调
$serv->on('ManagerStop', function (Swoole\Server $server) {
    echo '触发了ManagerStop事件回调' . PHP_EOL;
});


// worker/taskWorker进程启动时触发的回调
$serv->on('WorkerStart', function (Swoole\Server $server, int $worker_id) {
    echo '触发了WorkerStart事件回调，' .
        ($server->taskworker === true ?
            'taskWorker进程PID为' . $server->worker_pid :
            'Worker进程PID为' . $server->worker_pid) . PHP_EOL;
    // 使用php的Fatal Error模拟进程异常退出的错误，进程出错就可以触发WorkerError事件回调
    // 一般情况都会在业务代码使用try catch捕获异常，防止进程异常重启
    // 使用命令：php server.php >> test.txt   将命令行打印的信息输出到文本文件中查看
    // new fsfsdfds();
});
// worker/taskWorker进程异常触发的回调
$serv->on('WorkerError', function (Swoole\Server $server, int $worker_id) {
    echo '触发了WorkerError事件回调' . PHP_EOL;
});
$serv->on('WorkerExit', function (Swoole\Server $server) {
    echo '触发了WorkerExit事件回调' . PHP_EOL;
});
// 进程结束会触发此回调函数
$serv->on('WorkerStop', function (Swoole\Server $server, int $worker_id) {
    echo "触发了WorkerStop事件回调" . PHP_EOL;
});

$serv->on('Task', function (Swoole\Server $server, int $task_id, int $src_worker_id, $data) {
    echo '接收到了worker进程投递过来的任务，收到投递任务时发送的信息如下' . PHP_EOL;
    var_dump($data);
    // 通知worker进程任务处理完成
    sleep(3);
    $server->finish('task finish');
});
// 任务完成后的回调处理函数
$serv->on('Finish', function (Swoole\Server $server, int $task_id, string $data) {
    echo '接收到了任务完成的通知，任务完成传递过来的数据如下' . PHP_EOL;
    var_dump($data);
});


// 客户端建立连接时触发的回调
$serv->on('Connect', function (Swoole\Server $server, int $fd, int $reactorId) {
    echo '触发了Connect事件回调' . PHP_EOL;
});
// 收到客户端发送过来的数据时触发的回调，
//如果启动的是udp服务，监听的是onPacket事件
$serv->on('Receive', function (Swoole\Server $server, int $fd, int $reactor_id, string $data) {
    echo '触发了Receive事件回调' . PHP_EOL;
    $server->task('发送给taskWorker进程的数据');
});
// 客户端或者服务端断开连接时触发的回调
$serv->on('Close', function (Swoole\Server $server, int $fd, int $reactorId) {
    echo '触发了Close事件回调' . PHP_EOL;
});

$serv->start();