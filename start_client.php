<?php
/**
 * 穿山甲内网代理客户端
 * Created by taping.
 * User: Alen
 * Date: 2018/4/21
 * Time: 22:19
 */
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . "/Common/Comm.func.php";
require_once __DIR__ . "/Common/Config.php";
$worker = new Worker();
// 启动1个进程对外提供服务
$worker->count = 1;
$server_path = "ws://{$config['server_path']}:{$config['server_port']}";//主服务器地址
$client_name = "pangolin_proxy_client";//客户端名称
$remote_id="";//远程ID
$ping_time = 0;//主服务器数据包发送过来的时间
$con = "";
$worker->onWorkerStart = function($worker)use($server_path,$client_name){

    $con = new AsyncTcpConnection($server_path);
    $con->onConnect = function($con) {
        global $client_name;
        global $remote_id;
        global $ping_time;
        $ping_time = time();
        $con->send(json_encode(array("type"=>"reg","client_name"=>$client_name,"remote_id"=>$remote_id)));
        dumplog("server_conn",array("type"=>"reg","client_name"=>$client_name,"remote_id"=>$remote_id));
    };
    $con->onMessage = function($con, $data) {
        global $ping_time;
        $msg_data = json_decode($data,true)?json_decode($data,true):$data;
        $ping_time = time();
        dumplog("server_conn",$msg_data);
        if($msg_data['type']=="ping"){//心跳检测
            console_log("pong");
            $con->send(json_encode(array(["type"=>"pong"])));
        }else{//http代理
            http_proxy($con,$msg_data);
        }
    };
    //连接断开了,实行断线重连
    $con->onClose = function($conn)
    {
        $conn->reConnect(1);
        dumplog("server_conn","connection closed");
        echo "connection closed\n";
    };
    $con->connect();

};


/**
 * http代理
 * @param $connection
 * @param $data
 */
function http_proxy($connection,$data){
    if(isset($data["data"])){
        $buffer = base64_decode($data["data"]);
        $remote_id = $data["remote_id"];
        $url_data = [];
        getData($url_data,$buffer,"router");//获取请求参数
        $method = $url_data["method"];

        @$addr = !isset($url_data['port']) ? "{$url_data['host']}:80" : "{$url_data['host']}:{$url_data['port']}";
        $remote_connection = new AsyncTcpConnection("tcp://$addr");
        $remote_connection->onMessage = function ($conn,$data) use($connection,$remote_id){
            $data = array("type"=>"http_proxy_result","data"=>base64_encode($data),"remote_id"=>$remote_id);
            $connection->send(json_encode($data));
        };
        // 连接
        if ($method !== 'CONNECT') {
            $remote_connection->send($buffer);
        } else {
            $remote_connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
        }
        $remote_connection->connect();
    }
}

/**
 * http参数分解
 * @param $url_data
 * @param $buffer
 * @param string $type
 */
function getData(&$url_data,$buffer,$type="router"){
    global $config;
    if($type=="router"){
        // 处理http请求头
        $tmp = explode("\r\n", $buffer);
        $http_data = [];
        foreach ($tmp as $item){
            if(empty($http_data)){
                $item1 = explode(" ",$item);
                $http_data["Method"] = $item1[0];
            }else{
                $item1 = explode(":",$item);
                @$http_data[$item1[0]] = trim($item1[1]);
            }
        }
        $url_data["port"] = $config['router_port'];
        $url_data["host"] = $http_data["Host"]?$http_data["Host"]:"localhost";
        $url_data["method"] = $http_data["Method"];
    }else{
        // 处理http请求头
        list($method, $addr, $http_version) = explode(' ', $buffer);
        $url_data = parse_url($addr);
        $url_data["method"] = $method;

    }
}

/**
 * 写入日志
 * ($file_name,$data)
 */
function dumplog()
{
    $path = __DIR__ . "/log/";
    $ext = ".log";
    try{
        $args = func_get_args();
        $filename = array_shift($args);
        $path = $path.date("Y-m-d",time())."/";
        if(!is_dir($path)){
            mkdir($path,"0777",true);//创建文件夹
            chmod($path,0777);//设置权限
        }
        $myfile = @fopen("{$path}{$filename}{$ext}", "a");
        $time = date("Y-m-d H:i:s",time());
        @fwrite($myfile, "*******************{$time}*****************\r\n");
        foreach ($args as $val )
        {
            if(gettype($val)!="string"){
                $val = json_encode($val);
            }
            var_dump($val);
            @fwrite($myfile, $val."\r\n");
        }
        @fwrite($myfile, "***********************************************\r\n");
        @fclose($myfile);
    }catch (Exception $e){
        var_dump("{$path}{$filename} 写入失败");
        dumplog("errorlog","{$path}{$filename} 写入失败");
    }
}

Worker::runAll();