<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 消息分发主要逻辑
 * 主要是处理 onMessage onClose 
 */
use \GatewayWorker\Lib\Gateway;

class Events
{
   
   /**
    * 有消息时
    * @param int $client_id
    * @param mixed $message
    */
   public static function onMessage($client_id, $message)
   {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }
        
        // 根据类型执行不同的业务
        switch(@$message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            case 'reg':
//                var_dump($message_data['client_name']);
                $old_client_id = Gateway::getClientIdByUid($message_data['client_name']);
                //绑定新的ID之前先解绑老的ID
                if($old_client_id){
                    foreach ($old_client_id as $k => $v){
                        Gateway::unbindUid($v,$message_data['client_name']);
                    }
                }
                //绑定别名
                Gateway::bindUid($client_id,$message_data['client_name']);
                $data = array_merge($message_data,array("type"=>"client_inline","client_ip"=>"{$_SERVER['REMOTE_ADDR']}"));
                //客户端上线,发送广播通知所有客户端
                Gateway::sendToAll(json_encode($data));
                break;
            case 'http_proxy':
                //向代理机发送代理请求
                Gateway::sendToUid("pangolin_proxy_client",json_encode($message_data));
                break;
            case 'http_proxy_result':
                //将代理的信息返回给请求机
                Gateway::sendToUid("pangolin_server",json_encode($message_data));
                break;
            case 'remote_option_result'://远程操作信息返回
                //将代理的信息返回给请求机
                Gateway::sendToUid("pangolin_server",json_encode($message_data));
                break;
        }
   }
   
   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
       // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
       
       // 从房间的客户端列表中删除
       if(isset($_SESSION['room_id']))
       {
           $room_id = $_SESSION['room_id'];
           $new_message = array('type'=>'logout', 'from_client_id'=>$client_id, 'from_client_name'=>$_SESSION['client_name'], 'time'=>date('Y-m-d H:i:s'));
           Gateway::sendToGroup($room_id, json_encode($new_message));
       }
   }
  
}
