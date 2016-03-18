<?php
/*
 * www.huiweishang.com
 *
 * The program developed by ShouJieLi core architecture, individual all rights reserved,
 * if you have any questions please contact 1335244575@qq.com
 */

require_once(dirname(__FILE__) . "/../../config/init.php"); //公用引导启动文件
$time_start = H :: getmicrotime(); //开始时间

// 动作处理
call_mfunc();

die ('{"code":"1","msg":"方法无返回","data":""}');


// ************* 函数方法 ***************
//登录
function m__list(){
	//echo 11111;
	//exit;
    if(session::init()->is_login()) {//已经登录，重新登录
        $U = new user();
        if($_POST['login_name']=='') die('{"code":"1","msg":"请输入手机号/帐号","id":"login_name"}');
        
        $user = $U->get($_POST['login_name']);
        //设置登录状态
        $session_info=$U->set_session($user['uid']);
        
        $data = array();
        $data['uid'] = $user['uid'];
        
        //升级用户|合伙人身份
        $U->update_user_partner($user['uid']);
    
        //合伙人判断逻辑
		$user_partner = $U->getPartnerInfo($user['uid']);
		//$user_partner = $U->get_user_partner($user['uid']); 
        if($user_partner){
    	  //更新合伙人数据	    	
		  $data['partner_flag'] = $user_partner['vip_type'];
		  $data['partner_img'] = $user_partner['img'];
        }else{
    	  $data['partner_flag'] = 0;
		  $data['partner_img'] = "";
        }
     
        
        $data['nick_name'] = $user['nick_name'];
        $data['huxin_pass'] = $U->get_huanxin_pass($user['uid']);
        $data['mob'] = $user['login_mobile'];
        $data['sid'] = session::init()->get_session_name();
        $data['avatar'] = $user['avatar'];
        $data['true_name'] = $user['true_name'];
        //判断是否强制完善资料才可以进入APP
        $data['open_window'] = 0;//不打开完善资料
        if(!$U->account_complete($user['uid'])) $data['open_window']=1;//打开完善资料
        $data['ignore_window'] = 0;//可忽略
        
        $data['notify_status'] = $user['notify_status'];
        $data['notify_sound'] = $user['notify_sound'];
        $data['mobile_flag'] = $user['mobile_flag'];

        //回收各种 对象
        unset($redis,$U,$user);
        $ret = array('code'=>'0','msg'=>'登录成功','data'=>$data);
        
        if(API_JSON_CHINESE==1){
            die(H::json_encode_ch($ret));
        }else{
            die(json_encode($ret));
        }
        //die('{"code":"1","msg":"您已经登录了'.session::init()->get('uid').'"}');
    }
    //处理数据
    $_POST['login_pass'] = isset($_POST['login_pass']) ? $_POST['login_pass'] : '';
    $_POST['login_name'] = isset($_POST['login_name']) ? H::sqlxss(strtolower($_POST['login_name'])) : '';

    $platform = $_POST['platform'] = isset($_POST['platform']) ? $_POST['platform'] : '';
    
    //检查帐号是否输入
    if($_POST['login_name'] == '') die('{"code":"1","msg":"请输入手机号/帐号","id":"login_name"}');
    //检查密码是否输入
    if($_POST['login_pass'] == '') die('{"code":"1","msg":"请输入密码","id":"login_pass"}');
    
    //检查请求来源平台platform
    if(!empty($_POST['platform'])){
    	$platform = trim($_POST['platform']);
    }
  
    $U = new user();
    
    //查询帐号是否存在
    $user = $U->get($_POST['login_name']);//var_dump($user);
    
    //app登录特殊限制
    //if($platform=='ios' || $platform=='android'){
    //	if($user['utype']==2)  die('{"code":"1","msg":"此账号无法登录！"}');
    //}
    
    //供货商平台登录用户
    if($platform=='sp'){
    	if($user['utype']==1) die('{"code":"1","msg":"此账号无法登录供货商管理平台！"}');
    }    
  
    if($user['login_status']!=0)  die('{"code":"1","msg":"账号被禁用"}');
    if(!$user) die('{"code":"1","msg":"帐号密码不匹配1","id":"login_name"}');
    if($user['login_pass'] != H::password_encrypt_salt($_POST['login_pass'], $user['login_salt'])) {
        die('{"code":"1","msg":"帐号密码不匹配2","id":"login_pass"}');
    }

    //记录登录信息
    $fields = array(
        'login_ip'=>H::getip(),
        'login_num'=>($user['login_num']+1),
        'login_time'=>time(),
    );
    $dbm = database::init();
    $dbm->single_update(DB_DBNAME_USER.".ws_user",$fields,"uid='{$user['uid']}'");
    $U->set_user_info($user['uid']);
    
    //设置登录状态
    $session_info=$U->set_session($user['uid']);
    $data = array();
    
    //升级用户|合伙人身份
    $U->update_user_partner($user['uid']);
    
    //合伙人判断逻辑
	$user_partner = $U->getPartnerInfo($user['uid']);
    //$user_partner = $U->get_user_partner($user['uid']); 
    if($user_partner){
    	//更新合伙人数据	    	
		$data['partner_flag'] = $user_partner['vip_type'];
		$data['partner_img'] = $user_partner['img'];
    }else{
    	$data['partner_flag'] = 0;
		$data['partner_img'] = "";
    }
    
    $data['uid'] = $user['uid'];
    $data['nick_name'] = $user['nick_name'];
    $data['huxin_pass'] = $U->get_huanxin_pass($user['uid']);
    $data['mob'] = $user['login_mobile'];
    $data['sid'] = session::init()->get_session_name();
    $data['avatar'] = $user['avatar'];
    $data['true_name'] = $user['true_name'];
    
    $data['open_window'] = 0;
    //if(!$U->account_complete($user['uid'])) $data['open_window']=1;
    $data['ignore_window'] = 0;//可忽略
    
    $data['notify_status'] = $user['notify_status'];
    $data['notify_sound'] = $user['notify_sound'];
    $data['mobile_flag'] = $user['mobile_flag'];

    //回收各种 对象
    unset($redis,$U,$user);
    $ret = array('code'=>'0','msg'=>'登录成功','data'=>$data);
    
    if(API_JSON_CHINESE==1){
        die(H::json_encode_ch($ret));
    }else{
        die(json_encode($ret));
    }
}
