<?php

// 可以是域名或者ip地址，
// 如果是域名就需要通过在本地hosts文件加入  127.0.0.1  swoole-demo.com
$host = 'swoole-demo.com';
$port = '9501';
$http_server = new swoole_http_server($host, $port);

// 启动server前的配置项，暂不做详细介绍，http-server和websocket-server都是继承自server，server再对set方法做详细介绍
$options = [

];
$http_server->set($options);

// 注册request事件回调函数
$http_server->on('request', function ($request, $response) {
    // $request对象携带了请求信息，比如使用$_SERVER,$_GET,$_POST,$_COOKIE等，
    // $response响应对象，比如设置响应头，设置cookie、响应内容等
    // 上诉对象详细内容请查看官方文档介绍
    var_dump($request);
    var_dump($response);

    // 响应信息
    $response->end('<h1>hello world!!!</h1>');
});

// 启动http-server
$http_server->start();