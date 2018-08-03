<?php
/**
 * 公网代理映射服务
 * Created by taping.
 * User: Alen
 * Date: 2018/4/21
 * Time: 22:19
 */
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . "/../../Common/Comm.func.php";
require_once __DIR__ . "/../../Common/Config.php";
$worker = new Worker("tcp://0.0.0.0:{$config['mapping_port']}");//公网访问端口
// 启动1个进程对外提供服务
$worker->count = 1;
$server_path = "ws://{$config['server_path']}:{$config['server_port']}";//消息分发服务器地址
$client_name = "pangolin_server";//服务名称
$chat_conn = null;
$worker->name = $client_name;
$worker->onWorkerStart = function($worker)use($server_path,$client_name,&$chat_conn){
    $con = new AsyncTcpConnection($server_path);
    $chat_conn = $con;
    $con->onConnect = function($con) {
        global $client_name;
        $con->send(json_encode(array("type"=>"reg","client_name"=>$client_name)));
    };

    $con->onMessage = function($con, $data) use($worker) {
        global $config;
        $msg_data = json_decode($data,true)?json_decode($data,true):$data;
        if($msg_data['type']=="ping"){//ping
            console_log("pong");
            $con->send(json_encode(array(["type"=>"pong"])));
        }
        if($msg_data['type']=="client_inline"&&$msg_data['client_name']=="client"){//客户端上线
            $client_ip = $msg_data['client_ip'];//客户端公网ip
            $notify_url = $config["client_inline_path"];//上线通知回调地址
            $remote_id = $msg_data['remote_id'];//远程连接ID
            if($remote_id){
                //执行上线回调
                $res = asyncHttpRequest($notify_url,$msg_data);
                @$conn = $worker->connections[$remote_id];
                if($conn){
                    sendHttpResult(json_encode(array("code"=>1,"message"=>$msg_data)),$conn);
                }
                console_log("客户端上线了,通知远程地址{$notify_url}! {$client_ip}  {$res}");
            }


        }
        if($msg_data['type']=="http_proxy_result"){//http代理返回信息
            $html =  base64_decode($msg_data["data"]);
            $remote_id = $msg_data["remote_id"];
            @$conn = $worker->connections[$remote_id];
            if($conn){
                $conn->send($html);
            }
        }
    };
    //连接断开了,实行断线重连
    $con->onClose = function($conn)
    {
        $conn->reConnect(1);
        echo "connection closed\n";
    };
    $con->connect();
};
$worker->onMessage = function ($conn, $http_buffer)use(&$chat_conn){
    $request = \Workerman\Protocols\Http::decode($http_buffer,$conn);
    $type = getParam($request,"type");
    //网络代理,将当前网络请求数据包转发到内网主机
    $chat_conn->send(json_encode(array("type"=>"http_proxy","data"=>base64_encode($http_buffer),"remote_id"=>$conn->id)));

};

Worker::runAll();