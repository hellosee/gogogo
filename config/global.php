<?php
/*
 * 宏定义文件
 */
 
 
//调试使用，生产环境关闭
define('SQL_LOG','1');//耗时SQL记录
define('SQL_ERR_LOG','1');//错误SQL记录

//缓存设置
define('REDIS_SERVER_HOST','127.0.0.1');//220.
define('REDIS_SERVER_PORT','6379');


define('DOMAIN_JS','192.168.0.100');//JS跨域
define('DOMAIN_WWW','http://www.wsq.com');//微商圈主站域名
define('DOMAIN_USER','http://user.wsq.com');//用户中心域名
define('DOMAIN_CSS','http://css.wsq.com');//样式表域名
define('DOMAIN_UPLOAD','http://s3.wsq.com');//资源服务器域名
define('DOMAIN_ADMIN','http://hwsadm.wsq.com');//后台管理域名
define('DOMAIN_TOOL','http://tool.wsq.com');//工具下载
define('DOMAIN_APP','http://app2.wsq.com');//APP接口