<?php

//自动加载 类文件
function __autoload($class_name) {
    /*加载全部公用类库文件*/
    if(file_exists(ROOT_PATH . "/../class/".$class_name.".class.php")) {
        require(ROOT_PATH . "/../class/".$class_name.".class.php");
    }
    /*加载当前应用类库文件*/
    if(defined('APP_PATH') && APP_PATH!='') {
        if(file_exists(APP_PATH . "/../class/".$class_name.".class.php")) {
            require(APP_PATH . "/../class/".$class_name.".class.php");
        }
    }
}

/**
 * 动作处理函数调用，占用固定GET参数 m
 */
function call_mfunc(){

    $_GET['m'] = isset($_GET['m'])?$_GET['m']:'list';
    $_GET['m']=H::sqlxss($_GET['m']);//print_r($_GET);
    if (function_exists("m__" . $_GET['m'])) {
        call_user_func("m__" . $_GET['m']);
    }else{
        if($_GET['m']!='list'){
            die(' <b>m__'.$_GET['m'].'</b> function is not exists(Code:003)');
        }
    }
}

/**
 * 发送HTTP状态
 * @param integer $code 状态码
 * @return void
 */
function send_http_status($code) {
    static $_status = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
    );
    if(isset($_status[$code])) {
        header('HTTP/1.1 '.$code.' '.$_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:'.$code.' '.$_status[$code]);
    }
}

/**
 * Fetch all HTTP request headers
 * @return  数组
 */
function get_all_headers() {
    $header = array();
    if(function_exists('getallheaders')) {
        $header = getallheaders();
        if(is_array($header)) $header = array_change_key_case($header,CASE_LOWER);
    }else{
        foreach ($_SERVER as $key => $value) {
            if(stripos($key, 'HTTP_') !== false) {
                $header[strtolower(substr($key, 5))] = $value;
            }
        }
    }
    return $header;
}

/*
 * 高效率计算文件行数
 * @author axiang
 */
function countFileLine($file){
	$fp=fopen($file, "r");
	$i=0;
	while(!feof($fp)) {
		//每次读取2M
		if($data=fread($fp,1024*1024*2)){
			//计算读取到的行数
			$num=substr_count($data,"\n");
			$i+=$num;
		}
	}
	fclose($fp);
	return $i;
}

