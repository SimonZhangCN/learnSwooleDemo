<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 3/7/19
 * Time: 11:54 AM
 */

$host = "swoole-demo.com";
$port = 9501;
$ws_server = new Swoole\WebSocket\Server($host, $port);

// server有一个属性connections记录了所有的连接，当然也包括通过http请求的连接
// 为了避免向不是websocket的连接发送信息，
// 所以这里使用了swoole的内存表记录所有websocket客户端连接
// 表中只有一列name字段
$fd_table = new swoole_table(1024);
$fd_table->column('name', \Swoole\Table::TYPE_STRING, 20);
$fd_table->create();

// 使用内存表记录用户发送的信息
// 存在三列字段fd,name,message
$msg_table = new swoole_table(1024);
$msg_table->column('fd', \Swoole\Table::TYPE_INT);
$msg_table->column('name', \Swoole\Table::TYPE_STRING, 20);
$msg_table->column('message', \Swoole\Table::TYPE_STRING, 200);
$msg_table->create();

// 监听了request事件用于处理http请求，设置根目录让用户能访问到index.html
$ws_server->set([
    'document_root' => '/Users/simon/Study/backEnd/swoole-demo',
    'enable_static_handler' => true,
]);


// 1.客户端和服务端建立连接握手成功后会回调此函数，
// 2.swoole内置了握手，如需自己实现握手可通过绑定handshake事件回调函数，
// 3.自行实现握手后，将不会自动触发open事件
// 4.open和handshake都是可选的
$ws_server->on('open', function (Swoole\WebSocket\Server $server, $request) use($fd_table, $msg_table) {
    // 客户端连接到服务器并完成握手的回调处理函数
    $cur_fd = $request->fd;
    $name = '用户' . $cur_fd;
    // 通知所有用户，有新用户进入聊天室了
    foreach ($fd_table as $fd => $row) {
        $server->push($fd, $name . '进入聊天室了');
    }
    // 将建立连接的用户记录到内存表中
    $fd_table->set($cur_fd, [
        'name' => $name
    ]);
    echo '当前连接的客户端总数'. $fd_table->count(). PHP_EOL;
    // 最多发送10条聊天记录过去
    if ($msg_table->count() > 0) {
        $all_msg = [];
        foreach ($msg_table as $row) {
            $all_msg[] = $row;
        }
        $offset = $msg_table->count() >= 10 ? $msg_table->count() - 10 : 0;
        $data = array_slice($all_msg, $offset, 10);
        $server->push($cur_fd, json_encode($data));
    }
});

// message事件回调必须要设置,否则启动server的时候会失败，open和handshake可以不设置
// message事件用于处理客户端主动发送过来的消息
$ws_server->on('message', function (Swoole\WebSocket\Server $server, $frame) use($ws_server, $fd_table, $msg_table) {
    // 向除自己以外的用户发送信息
    $cur_fd = $frame->fd;
    $client_msg = $frame->data;
    $name = $fd_table->get($cur_fd)['name'];
    $msg = "{$name}：{$client_msg}";
    // 模拟用户发送不雅内容时，将用户踢出聊天室
    if ($client_msg == 'fuck') {
        $ws_server->disconnect($cur_fd, 1000, '发送不雅信息，已被赶出聊天室');
        $fd_table->del($cur_fd);
        $msg = $name . '被移除出聊天室';
    } else {
        // 将信息写入到表中
        $msg_table->set(time(), [
            'fd' => $cur_fd,
            'name' => $name,
            'message' => $msg
        ]);
    }
    // 向所有用户（不包括自己）发送消息
    foreach ($fd_table as $fd => $val) {
        if ($fd != $cur_fd) {
            $server->push($fd, $msg);
        }
    }
});


// 客户端断开连接时的处理事件
// ws和http连接底层都会占用一个tcp连接，所以http类请求会在客户端一定时间内没使用时自动断开，也会触发close事件
// 下面只对ws的连接断开进行对在线用户的通知
$ws_server->on('close', function ($ser, $cur_fd) use($ws_server, $fd_table)  {
    // 判断是否是websocket客户端断开连接
    if ($ws_server->isEstablished($cur_fd)) {
        // 删除断开连接客户端
        $name = $fd_table->get($cur_fd)['name'];
        $fd_table->del($cur_fd);
        foreach ($fd_table as $fd => $val) {
            $ser->push($fd, $name.'离开了聊天室');
        }
    } else {
        echo '非webwocket客户端断开连接'.PHP_EOL;
    }
});

// request事件回调函数
$ws_server->on('request', function ($request, $response) use($fd_table, $ws_server) {
    // ws-server继承自https-server，开启服务同时，设置了request的事件回调，即可接收http请求并处理
    // 可以通过手动发送一个http请求，根据请求来向所有连接的ws客户端主动推送消息
    if ($request->server['path_info'] == '/broadcast') {
        foreach ($fd_table as $fd => $val) {
            $ws_server->push($fd, '广播消息');
        }
        $response->end('broadcast success');
    }
});

$ws_server->start();