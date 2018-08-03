<?php
/**
 * Created by taping.
 * User: Alen
 * Date: 2018/4/21
 * Time: 8:34
 */
$config =  [
    "mapping_port"=>"4567",//映射的外网端口(将内网映射到外网的mapping_port上,通过外网ip加上此端口设置为代理即可将请求映射到内网)
    "server_path"=>"192.168.199.239",//公网服务器地址
    "router_port"=>"80",//客户端路由端口 网络请求都将被转发到客户端的此端口上
    "server_port"=>"7272",//消息分发服务端口(此端口用户消息转发,一般不需要修改)
    "client_inline_path"=>"http://ip.chinaz.com/getip.aspx",//客户端上线回调地址
];