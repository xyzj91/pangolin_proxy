<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/20 0020
 * Time: 上午 10:23
 */

use Workerman\Connection\AsyncTcpConnection;

/**
 * get获取参数
 * @param $data
 * @param string $key
 * @return mixed
 */
function getParam($data,$key=""){
    return $key?@$data["get"][$key]:$data["get"];
}

/**
 * POST获取参数
 * @param $data
 * @param string $key
 * @return mixed
 */
function postParam($data,$key=""){
    return @$key?$data["post"][$key]:$data["get"];
}

/**
 * get请求
 * @param AsyncTcpConnection $conn
 * @param array $data
 */
function sendData($conn,$data=array(),$type = "GET"){
    $data_str = http_build_query($data);
    $conn->send("{$type} /?{$data_str} HTTP/1.1\r\nConnection: keep-alive\r\nContent-Type:text/html;charset=UTF-8\r\n\r\n");
}

/**
 * 绑定别名
 * @param $uid
 * @param $alias
 */
function bindAlias($uid,$alias){
    global $uid_client_name_mapping;
    if($uid!=null){
        $uid_client_name_mapping[$uid] = $alias;
    }
}

/**
 * 根据UID获取别名
 * @param $uid
 * @return bool
 */
function getAliasByUid($uid){
    global $uid_client_name_mapping;
    if($uid!=null){
        return $uid_client_name_mapping[$uid];
    }
    return false;
}

/**
 * 解绑
 * @param $uid
 */
function unBindAlias($uid){
    global $uid_client_name_mapping;
    if($uid!=null){
        $uid_client_name_mapping[$uid] = null;
    }
}


/**
 * @param $worker_group
 * @param $client_name
 * @return bool
 */
function getConnectByClientName($client_name){
    global $worker_group;
    $conn  = @$worker_group[$client_name];
    if($conn){
        return $conn;
    }
    return false;
}

/**
 * @param $client_name
 * @param $conn
 */
function setConnectByClientName($client_name,$conn){
    global $worker_group;
    $worker_group[$client_name] = $conn;
}

function sedMessageByClientName($client_name,$data=array()){
    $conn  = getConnectByClientName($client_name);
    if($conn){
        $conn->send(json_encode($data));
        return true;
    }
    return false;
}

/**
 * http协议状态响应
 * @param $data
 * @param $conn
 */
function sendHttpResult($data,$conn){
    $content = \Workerman\Protocols\Http::encode($data,$conn);
    $conn->send($content);
}

/**
 * 输出日志
 * @param $data
 */
function console_log($data){
    if(is_array($data)){
        $data = json_encode($data);
    }
    echo $data."\r\n";
}

/**
 * 异步发送数据
 * @param $url
 * @param $data
 */
function asyncHttpRequest($url,$data){
    $data = is_array($data)?http_build_query($data):$data;
    $testurl = "{$url}?{$data}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testurl);
    //参数为1表示传输数据，为0表示直接输出显示。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //参数为0表示不带头文件，为1表示带头文件
    curl_setopt($ch, CURLOPT_HEADER,0);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
