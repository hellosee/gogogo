<?php

// 判断app登录状态
function check_app_login() {
    if(session::init()->is_login() == false) die('{"code":"2","msg":"登录状态已失效"}');
}

/**
 * 替换正文中 huiweishang.com 的图片为缩略图 宽度480
 */
function thumb_images($content='', $size=480){
     $content = H::sqlxss_decode($content);
     $reg = '~<img[^>]* src="(.*?)"[^>]*>~';
     preg_match_all($reg, $content, $arr);

     foreach($arr[1] as $v){
         if(!strstr($v,'.huiweishang.com')) continue;
         $content=str_replace($v,H::preview_url($v,'preview',$size.'_'),$content);
     }
     return $content;
}


/**
 * 提取正文中 huiweishang.com 的图片，返回图片数组
 */
function get_info_body_img($info_body,$size=480) {
    $info_body_img  = array();
    $info_body = H::sqlxss_decode($info_body);//die($info_body);
    $reg = '~<img[^>]* src="(.*?)"[^>]*>~';
    preg_match_all($reg, $info_body, $arr);
    if(!empty($arr[1])) {
        foreach($arr[1] as $k1=>$v1) {
            if($k1>8) break;
            if(strstr($v1,'192.168.0.')){//本地测试全替换
                
            }else{
                if(!strstr($v1,'.huiweishang.com')) continue;
            }
            if($size>0){ 
                array_push($info_body_img,H::preview_url($v1,'preview',$size.'_'));
            }else{
                array_push($info_body_img,$v1);
            }
        }
    }
    return $info_body_img;
}

//postCurl方法
function postCurl($url, $body, $header = array(), $method = "POST") {
    array_push($header, 'Accept:application/json');
    array_push($header, 'Content-Type:application/json');

    $ch = curl_init();//启动一个curl会话
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, $method, 1);

    switch ($method){
        case "GET" :
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        break;
        case "POST":
            curl_setopt($ch, CURLOPT_POST,true);
        break;
        case "PUT" :
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        break;
        case "DELETE":
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        break;
    }

    curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    if (isset($body{3}) > 0) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if (count($header) > 0) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    $ret = curl_exec($ch);
    $err = curl_error($ch);

    curl_close($ch);

    if ($err) {
        return $err;
    }

    return $ret;
}
function p($obj=array(),$type = 0){
	echo '<pre>';
	print_r($obj);
	echo '</pre>';
	$type && exit;
}