<?php

require_once(dirname(__FILE__) . "/../../config/init.php"); //公用引导启动文件
$time_start = H :: getmicrotime(); //开始时间

// 动作处理
call_mfunc();

die ('{"code":"1","msg":"方法无返回","data":""}');

// 模板处理

// ******************************************************* 函数方法 *******************************************************
//使用说明，帮助类型信息显示，调用话题标题和正文（正文可采用上传多图方式）
function m__list(){
    //print_r($_POST);
    $post_id=isset($_POST['id'])?intval($_POST['id']):0;//id
    
    $alias=isset($_POST['alias'])?$_POST['alias']:'';//指定类型别名方便调整ID
    if($alias=='task_share') $post_id=API_DEBUG==1?300332:1213938;//加粉 > 任务中心
    if($alias=='task_help') $post_id=API_DEBUG==1?300332:1338519;//加粉 > 使用帮助
    if($alias=='vip_personal') $post_id=API_DEBUG==1?300352:1213941;//加V > 个人认证说明
    if($alias=='vip_enterprise') $post_id=API_DEBUG==1?300325:1213943;//加V > 企业认证说明
    if($alias=='help') $post_id=API_DEBUG==1?300324:1213939;//常见问题
    
	/*
		帮助中心 http://tool.huiweishang.com/app/help/
	*/
	if($alias == 'help_center'){
		$ret = array('code'=>0,'msg'=>'','data'=>array('url'=>'http://tool.huiweishang.com/app/help/'));
		if(API_JSON_CHINESE==1){
			die(H::json_encode_ch($ret));
		}else{
			die(json_encode($ret));
		}
	}
	
    $data=array();
    $alias_arr=explode('|',$alias);//print_r($alias_arr);
    if(count($alias_arr)>1){
        foreach($alias_arr as $v){
            if($v=='task_share') $post_id=API_DEBUG==1?300332:1213938;//加粉 > 任务中心
            if($v=='task_help') $post_id=API_DEBUG==1?300332:1338519;//加粉 > 使用帮助
            if($v=='vip_personal') $post_id=API_DEBUG==1?300352:1213941;//加V > 个人认证说明
            if($v=='vip_enterprise') $post_id=API_DEBUG==1?300325:1213943;//加V > 企业认证说明
            if($v=='help') $post_id=API_DEBUG==1?300324:1213939;//常见问题
            //echo(';1-'.$post_id);
            $data[$v]=get_quan_post_by_id($post_id);
        }
    }else{
        //echo(';2'.$post_id);
        if($alias==''){
            //echo(';3-'.$post_id);
            $data[$post_id]=get_quan_post_by_id($post_id);
        }else{
            //echo(';4-'.$post_id);
            $data[$alias]=get_quan_post_by_id($post_id);
        }
    }

    $ret = array('code'=>0,'msg'=>'','data'=>$data);
    
    if(API_JSON_CHINESE==1){
        die(H::json_encode_ch($ret));
    }else{
        die(json_encode($ret));
    }
}

function get_quan_post_by_id($post_id){
    $dbm = database::init();
    //$sql="select post_title,post_content from ".DB_DBNAME.".ws_quan_post where post_id='$post_id'";
    //$rs=$dbm->query($sql);//print_r($rs);
    $rs=$dbm->find(DB_DBNAME.".ws_quan_post","post_title,post_content","post_id='$post_id'");
    //print_r($rs);
    if($rs){
        $rs['post_content']=H::filter_html_app($rs['post_content']);
        $rs['post_content']=H::sqlxss_decode($rs['post_content']);
        $rs['post_content']=preg_replace('~<!--.*?-->~','',$rs['post_content']);
        $rs['post_content']='<style>*{line-height:170%;color:#333;}</style><div style="margin:10px 5px;">'.$rs['post_content'].'</div>';
    }else{
        $rs['post_content']='';
        $rs['post_title']='';
    }
    return $rs;
}
