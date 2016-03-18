<?php
define("DB_CHANGE",false);//切换数据库连接配置 true 线上测试，false 本地测试
//数据库编码
define('DB_CHARSET', 'utf8');
//主站数据库
define('DB_DBNAME', 'ws_www');
if(DB_CHANGE){
	define('DB_HOST', '***.***.***.***:3606');//数据库地址
	define('DB_USERNAME', 'lsj');//用户名
	define('DB_PASS', 'lishoujie2015#');//密码
} else{
	define('DB_HOST', 'localhost');//数据库地址
	define('DB_USERNAME', 'root');//用户名
	define('DB_PASS', 'root');//密码
}