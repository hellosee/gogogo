<?php
/*
 * www.huiweishang.com
 * 加V认证订单/支付操作
 */

require_once(dirname(__FILE__) . "/../../config/init.php"); //公用引导启动文件
require_once(dirname(__FILE__) . "/../../../class/pay/wxpay/wxpay_base.class.php"); 
require_once(dirname(__FILE__) . "/../../../class/pay/wxpay/app_pay.class.php"); //公用引导启动文件
$time_start = H :: getmicrotime(); //开始时间
$dbm = database::init();
//切换数据库 让其支持事务处理
$dbm->select_db('ws_shop');
// 动作处理
call_mfunc();

die ('{"code":"1","msg":"方法无返回","data":""}');

function m__list(){
	if(session::init()->is_login()) {
		$uid = isset($_POST['uid']) ? $_POST['uid'] : '';				
		$platform = isset($_POST['platform']) ? $_POST['platform'] : '';
		$type = isset($_POST['type']) ? $_POST['type'] : "NO_TYPE";
		$certtype = isset($_POST['cert_type']) ? $_POST['cert_type'] : "0";
		//检查帐号是否输入
		if($uid == '') die('{"code":"1","msg":"非法请求2","id":"login_name"}');
		//检查请求来源平台platform
		if(strtolower($platform)!='ios' && strtolower($platform) != 'android'){
			die('{"code":"1","msg":"此方式不能访问，请换其他方式","id":"platform"}');
		}

		switch(strtoupper($type)){
			case 'CREATE':
				$order_id = m__create($uid,$certtype);
				if($order_id == ""){die('{"code":"1","msg":"订单id错误","id":"order_id"}');}
				$ret = array('code'=>0,'msg'=>'成功','data'=>array('order_id'=>$order_id));
				if(API_JSON_CHINESE==1){
					die(H::json_encode_ch($ret));
				}else{
					die(json_encode($ret));
				}
				break;
			case 'WXPAY':
				$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';	
				if($order_id == ""){die('{"code":"1","msg":"订单id错误","id":"order_id"}');}
				m__wxpay($uid,$order_id);
				break;
			default:
				die('{"code":"1","msg":"type类型错误","id":"type"}');
		}
	} else {
		die('{"code":"1","msg":"非法请求1","id":"login_name"}');
	}
}

//APP微信支付
function m__wxpay($uid,$order_id){
	global $dbm;
	$oinfo = $dbm->find("ws_shop.ws_vip_order","*","order_id='{$order_id}'");
	if(!$oinfo){die('{"code":"1","msg":"用户订单不存在","id":"order"}');}
	$allow_status = array(0,1);
	if($oinfo['status']!='active'){
		die('{"code":"1","msg":"订单已处于完成状态或者取消状态不需要支付","id":"order"}');
	}
	if($oinfo['pay_status']==1){
		die('{"code":"1","msg":"订单已经支付","id":"order"}');
	}
	if(!in_array($oinfo['pay_status'],$allow_status)){
		die('{"code":"1","msg":"订单异常无法完成支付","id":"order"}');
	}
	$wxpay =  new  app_pay();
	//发起微信支付参数获取不响应返回APP
	$wx_pay_data = $wxpay->createOrder(array(
		'body'=>"汇微商加V认证支付费用".$oinfo['final_amount'].'元',
		'price'=>$oinfo['final_amount'],//100
		'order_id'=>"vip".rand(10,99).rand(10,99).rand(10,99).$order_id,
	));
	$ret = array('code'=>0,'msg'=>'','data'=>$wx_pay_data);
	if(API_JSON_CHINESE==1){
        die(H::json_encode_ch($ret));
    }else{
        die(json_encode($ret));
    }
}


//创建订单
function m__create($uid,$certtype){
	global $dbm;
	//查询该用户是否已经创建过订单array('uid'=>$account_id)
	$isExist =  $dbm->find("ws_shop.ws_vip_order","*","uid='{$uid}'");
	if($isExist){
		if($isExist['pay_status'] != 0){die('{"code":"1","msg":"订单已经支付","id":"order"}');}
		if($isExist['status'] != "active"){die('{"code":"1","msg":"订单已经关闭","id":"order"}');}
		//如果切换认证方式,修改订单认证字段
		if($certtype == 0){
			$params = array(
				'certtype'        =>$certtype,
				'price'           =>ADD_V_PERSONAL_PRICE,
				"final_amount"    =>ADD_V_PERSONAL_PRICE-ADD_V_PERSONAL_PRICE_D,
				'updatetime'      => time()
			);
		} else {
			$params = array(
				'certtype'        =>$certtype,
				'price'           =>ADD_V_COMPANY_PRICE,
				"final_amount"    =>ADD_V_COMPANY_PRICE-ADD_V_COMPANY_PRICE_D,
				'updatetime'      => time()
			);
		}
		$dbm->single_update("ws_shop.ws_vip_order",$params,"order_id='{$isExist['order_id']}'");
		unset($dbm);
		return $isExist['order_id'];
	}	
	mysql_query('START TRANSACTION');
	$order_id = m__getRandOrderID();
	//个人认证费用
	$price = ADD_V_PERSONAL_PRICE;//300.00
	$pmt_order = ADD_V_PERSONAL_PRICE_D;//优惠金额
	if($certtype){
		//企业认证费用
		$price = ADD_V_COMPANY_PRICE;
		$pmt_order = ADD_V_COMPANY_PRICE_D;
	}
	
	$orderinfo = array(
		'order_id'     => $order_id,
		'pay_status'   => 0,
		'createtime'   => time(),
		'uid'          => $uid,
		'price'        => $price,
		'pmt_order'    => $pmt_order,
		'final_amount' => $price-$pmt_order,
		'status'       => 'active',
		'updatetime'   => time(),
		'certtype'     => $certtype
		
	);
	$r = $dbm->single_insert("ws_shop.ws_vip_order",$orderinfo);
	if($r['error']){
		mysql_query('ROLLBACK');//回滚事务
		unset($dbm);
		die('{"code":"1","msg":"创建订单失败","id":"order"}');
	}
	mysql_query('COMMIT');
	unset($dbm,$orderinfo);
	return $order_id;
}
//生成订单号
function m__getRandOrderID(){
	list($t1, $t2) = explode(' ', microtime());
	$order_id = $t2.rand(10,99).rand(10,99).rand(10,99).rand(0,9);
    return $order_id;
}