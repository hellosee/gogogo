<?php
/*
 * 初始化文件
 */
 
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai'); //默认时区
error_reporting(-1); //报告所有错误，0为忽略所有错误
ini_set('display_errors', '1'); //开启错误提示
ini_set('magic_quotes_runtime', '0'); //魔法反斜杠转义关闭
ini_set('default_charset', 'utf-8'); //默认编码
define('MEMORY',memory_get_usage()); //初始化内存
define("ROOT_PATH",str_replace('\\','/',dirname(__FILE__)));//根目录物理路径
require_once(ROOT_PATH . "/../config/conn.php"); //数据库连接
require_once(ROOT_PATH . "/../config/global.php"); //基本配置
require_once(ROOT_PATH . "/../class/function.php"); //通用方法类

// 兼容 DOCUMENT_ROOT 变量
$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
if (!isset($_SERVER['QUERY_STRING']) || empty($_SERVER['QUERY_STRING'])) $_SERVER['QUERY_STRING']='';
if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) $_SERVER['REQUEST_URI']='';
if (!isset($_SERVER['HTTP_HOST']) || empty($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST']='command';

define('HTTP_HOST',$_SERVER['HTTP_HOST']);//die(HTTP_HOST);

$p = $_GET['p'] = isset($_GET['p'])?intval($_GET['p']):1; //分页页码
if ($p<=0) $p = 1;
//兼容POST提交，以POST为准
if(isset($_POST['p']) && intval($_POST['p'])>0) $p=$_POST['p'];

$_GET['tpl'] = isset($_GET['tpl']) ? trim($_GET['tpl']) : ''; //模板参数


$_COOKIE=H::sqlxss($_COOKIE);

?>



