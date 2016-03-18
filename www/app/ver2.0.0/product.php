<?php
/*
 * www.huiweishang.com
 *
 * The program developed by ShouJieLi core architecture, individual all rights reserved,
 * if you have any questions please contact 1335244575@qq.com
 */

require_once(dirname(__FILE__) . "/../../config/init.php"); //公用引导启动文件
require_once(dirname(__FILE__)."/../../../class/sphinxclient.class.php");
$time_start = H :: getmicrotime(); //开始时间

// 动作处理
call_mfunc();

die ('{"code":"1","msg":"方法无返回","data":""}');

// 模板处理

// ******************************************************* 函数方法 *******************************************************
//产品列表/搜索/我的/指定用户的产品
function m__list(){
    global $p;
    $_POST=H::sqlxss($_POST);
    $trade = $_POST['trade'] = isset($_POST['trade']) ? intval($_POST['trade']) : 0;//行业
    $kw = isset($_POST['kw'])?$_POST['kw']:'';//关键词
    $uid = isset($_POST['uid'])?intval($_POST['uid']):0;//是否查看自己的（登录状态）
    $my = isset($_POST['my'])?intval($_POST['my']):0;
    
    
    $redis = myredis::init();
    $dbm = database::init();
    $session = session::init();
        
    $params['table_name']=DB_DBNAME.".ws_page_info";
    $params['fields'] = "info_id,uid,info_title,info_img,product_words,info_body,create_time,is_del,is_front_display";
    $params['count'] = 0;
    
    $params['pagesize'] = PAGESIZE_APP;
    
    $params['where'] = " is_del=0 and cate_id=2";
    
    //兼容自己发布的产品查询
    if($my) {//如果是自己的
        check_app_login();
        $uid=$session->get('uid');
        $params['where'] .= " and uid=".$uid;
    }else{       
        //$params['where'] = " is_del=0 and cate_id=2";//审核通过没有屏蔽的产品     
        if($uid>0){
            $params['where'] .= " and uid=".$uid;
        } else {
            $params['where'] .= " and is_front_display=1";
        }
    }
    if($trade>0) $params['where'] .= " and trade=$trade";//这里增加is_front_display搜索条件
    //$params['where'] .= " and is_front_display=1";
    $params['suffix'] = " order by corder desc,create_time desc " .$dbm->get_limit_sql($params['pagesize'],$p);
    
    $search_result=1;
    //产品搜索查询coreseek全文索引
    if($kw!='') {
        $params['suffix'] = " order by create_time desc ";
        $cl = new SphinxClient ();
        $cl->SetServer (CSK_WS_PRODUCT_HOST, intval(CSK_WS_PRODUCT_PORT));
        $cl->SetArrayResult (true);
        $offset = ($p-1)*$params['pagesize'];
        $cl->SetLimits($offset, $params['pagesize']);
        //筛选行业
        if($trade > 0) {
            $cl->SetFilter('trade', array($trade));
        }
        $tmp = $cl->Query($kw, "*" );
        if(!$tmp) die('{"code":"1","msg":"'.$cl->GetLastError().'"}');
        $ids = array();
        //die(print_r($tmp['matches']));
        if(isset($tmp['matches'])) {
            foreach ($tmp['matches'] as $k=>$v ) {
                array_push($ids, $v['id']);
            }
        }
        //获取到ID，从表里增加指定ID查询条件
        if(count($ids)>0) {
            $params['where'] .=" and info_id in (".implode(',', $ids).")";
        }else{
            $params['where'] .=" and info_title='$kw'";
            //$search_result=0;
        }
    }
    
    //限制了UID查询不走强制索引
    if(strstr($params['where'],'uid=')){
        
    }else{
        $params['table_name'].=" force index(create_time) ";
    }
    
    //查询数据缓存
    $cache_key = 'list:product:p:'.$p.':trade:'.$trade.':kw:'.md5($kw).":uid:".$uid; //缓存的key值
    //$redis->del($cache_key);
    if($redis->get($cache_key)&& $uid==0) {
        $result = json_decode($redis->get($cache_key),1);  //读取缓存
    }else{
        if($search_result==1){
            $result  = $dbm->single_query($params);
        }else{
            $result = array('list'=>array(),'error'=>'');
        }
        if($result['error']!='') die('{"code":"1","msg":"查询数据出错"}');
        //非我的产品，写入缓存
        if($uid==0) $redis->set($cache_key,json_encode($result),60);
        unset($dbm,$redis); //销毁 对象
    }
    //$result  = $dbm->single_query($params);
    //echo('<pre>');print_r($result);
    $data=array();
    $U=new user();
    //重组数组
    foreach($result['list'] as $v){
        
        $user=$U->get($v['uid']);
		/*增加该用户是否是供货商*/
		$v['utype'] = $user['utype'];  //1=分销商 ， 2=供货商
        $v['tou_xian']=isset($user['tou_xian'])?$user['tou_xian']:'';//认证头衔
        $v['vip_auth']='0';//未认证微商
        if(isset($user['vip_status']) && $user['vip_status']==1){//已认证通过
            $v['vip_auth']='2';//个人认证（金V）
            if(isset($user['cert_type']) && $user['cert_type']==1){
                $v['vip_auth']='1';//企业认证（蓝V）
            }
        }
        $v['nick_name']=$user['nick_name'];
        $v['avatar']=H::preview_url($user['avatar'],'preview','100_');
        
        $v['create_time'] = H::datef($v['create_time'],'Y-m-d H:i');//转换时间
        
        $v['info_title'] = H::filter_desc($v['info_title'],60);
        //提取正文图片数组
        $v['info_body_img'] = get_info_body_img($v['info_body'],300);
        
        //正文没提取到，缩略图设置为唯一
        if(count($v['info_body_img'])==0 && $v['info_img']!=''){
            $v['info_body_img'][0] = H::preview_url($v['info_img'],'preview','300_');
        }
        foreach($v['info_body_img'] as $k1=>$v1){
            if($k1>2) unset($v['info_body_img'][$k1]);
        }
        //设置简介
        $v['info_body']=H::filter_desc($v['info_body'],180);
        if($v['product_words']!='') $v['info_body'] = H::filter_desc($v['product_words'],60);
        $v['is_del']=$v['is_del']==1?'已下架':'已上架';
        
        unset($v['info_img']);
        unset($v['product_words']);
       
        array_push($data,$v);
    }
    //print_r($data);die();
    $ret = array('code'=>'0','msg'=>'列表获取成功','data' =>$data);
    //echo('<pre>');die(print_r($ret));
    
    if(API_JSON_CHINESE==1){
        die(H::json_encode_ch($ret));
    }else{
        die(json_encode($ret));
    }
}
 