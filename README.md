穿山甲内网代理
=======
基于workerman的GatewayWorker框架开发的一款高性能代理系统。

GatewayWorker框架文档：http://www.workerman.net/gatewaydoc/

介绍
======
####pangolin_proxy是什么？
基于workerman的GatewayWorker框架开发的一款高性能代理系统。实现了内网IP映射到公网，实现了将公网请求代理到内网
####pangolin_proxy能做什么？

 * 内网搭建网络服务器，实现公网访问内网资源
 * 适用于需要公网联调的一些开发测试场景 例如：直接在内网调试支付，或者直接在内网调试微信公众号等，不必再将代码发到公网调试，提高开发效率

 特性
======
 * 使用socket协议
 * 请求代理转发
 * 断线重连，提高内网稳定性
  
下载安装
=====
1、git clone https://github.com/xyzj91/pangolin_proxy

运行流程
=====
1.将服务端部署到公网服务器上并启动
2.客户端修改配置文件：Common/Config.php 中的 server_path 修改为公网服务器的IP地址
3.启动客户端

启动停止(Linux系统)
=====
启动server端 （server端必须部署在公网服务器上）

```php start_server.php start -d ```

启动client端 （client端部署在内网机上） 
```php start_client.php start -d ```

启动(windows系统)
======
启动server端 （server端必须部署在公网服务器上）
双击start_server_for_win.bat  

启动client端 （client端部署在内网机上）
双击start_client_for_win.bat

注意：  
windows系统下无法使用 stop reload status 等命令  
如果无法打开页面请尝试关闭服务器防火墙  


