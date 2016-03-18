<?php
require_once(dirname(__FILE__) . "/../../config/init.php"); //公用引导启动文件

$dbm = database::init();
// 动作处理
call_mfunc();
die ('{"code":"1","msg":"方法无返回","data":""}');

function m__list(){
	echo 'haha';
}