<?php


require_once(dirname(__FILE__) . "/../../config/init.php"); //公用引导启动文件
$time_start = H :: getmicrotime(); //开始时间

// 动作处理
call_mfunc();

die ('{"code":"1","msg":"方法无返回","data":""}');

// 模板处理

// ******************************************************* 函数方法 *******************************************************
//获取代码
function m__list(){
    
    $_POST=H::sqlxss($_POST);
    
    $code_id = isset($_POST['id'])?intval($_POST['id']):0;
    if($code_id<=0) die('{"code":"1","msg":"参数错误"}');
    
    $code = tree::init()->get($code_id);
    if(!$code) die('{"code":"1","msg":"代码ID不存在"}');
    
    $code_data = array();
    foreach($code['son'] as $v) {
        array_push($code_data, array('id'=>$v['code_id'],'name'=>$v['txt']));
    }
    
    if($code_id==11428 || $code_id==1 || $code['parent_code_id']==1) {
        array_unshift($code_data, array('id'=>'0','name'=>'全部'));
    }
    $ret = array('code'=>0,'msg'=>'','data'=>$code_data);
   //die(print_r($ret));
    unset($code);
    
	if(API_JSON_CHINESE==1){
        die(H::json_encode_ch($ret));
    }else{
        die(json_encode($ret));
    }
    
}
 