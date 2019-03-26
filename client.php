<?php
set_time_limit(0);
$ip = '120.78.185.40';
$port = 8001;
if (!($socket = socket_create(AF_INET,  SOCK_STREAM, SOL_TCP))) {
        $errCode = socket_last_error();
        $errMsg = socket_strerror($errCode);
        die("Could not create socket:[$errCode] $errMsg\r\n");
}
echo "Socket is created\r\n";
echo "试图连接 $ip 端口 $port ...\r\n";
if (!socket_connect($socket, $ip,$port)) {
        $errCode = socket_last_error();
        $errMsg = socket_strerror($errCode);
        die("Could not connect:[$errCode] $errMsg\r\n");
}
echo "Connect established\r\n";
$message = "{jklasdfsdfjkldfsjkljklsdfjkl}\r\n";
$out = '';
if(!socket_write($socket, $message, strlen($message))) {
        $errCode = socket_last_error();
        $errMsg = socket_strerror($errCode);
        die("Could not connect:[$errCode] $errMsg\r\n");
}
echo "发送到服务器信息成功！\r\n";
echo "发送的内容为:$message\r\n";
while($out = socket_read($socket, 8192)) {
echo "接收服务器回传信息成功！\r\n";
    echo "接受的内容为:",$out,"\r\n";
}
echo "关闭SOCKET...\r\n";
socket_close($socket);
echo "关闭OK\r\n";
