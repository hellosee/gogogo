<?php
class user {
    private $dbm = null; //数据库操作对象
    private $redis = null; //redis 对象
    /**
     * 初始化对象
     * @param $dbm object 数据库操作对象
     */
    public function __construct() {
        $this->dbm = database::init();
        $this->redis = myredis::init();
        
    }
    /**
     * 添加单个用户
     * @return void  成功返回 自增ID 失败返回具体信息
     */
    public function add($fields) {
        //补充入库字段
        $fields['login_salt'] = H::security_code();
        $fields['login_pass'] = H::password_encrypt_salt($fields['login_pass'], $fields['login_salt']);
        $fields['create_ip'] = H::getip();
        $fields['create_time'] = time();
        $fields['mobile_flag'] = 1;
        $fields['reg_type']=2;
        $fields['avatar']='http://css.huiweishang.com/avatar/f2/f_'.rand(1,24).'.jpg';
        
        //敏感词过滤
        $filter_words=explode(',',FILTER_NICK);
        foreach($filter_words as $v){
            if(strstr($fields['nick_name'],$v)) $fields['login_status']='1';
        }
        
        //插入数据
        $a = $this->dbm->single_insert(DB_DBNAME_USER.".ws_user",$fields);
        $uid = $a['autoid'];
        if($uid<=0) return false;

        //注册送积分
        $tree = tree::init(); // 代码树 对象
        $code_id = 11438;
        $tmp_tree = $tree->get($code_id);
        $fields = array();
        $fields['point']=$tmp_tree['value'];
        $fields['uid'] = $uid;
        $fields['point_type'] = $code_id;
        $fields['create_time'] = time();
        $ret = $this->dbm->single_insert(DB_DBNAME_USER.".ws_point",$fields);
        
        //更新 redis 信息
        $userinfo = $this->dbm->find(DB_DBNAME_USER.".ws_user",'*',"uid='{$uid}'");
        $this->redis->set_array('user_info:'.$uid,$userinfo);
        
        //写入环信
        $this->dbm->single_insert(DB_DBNAME_USER.".ws_user_huanxin",array('reg_status'=>0,'uid'=>$uid,'huanxin_upass'=>rand(100000,999999).$uid));  
        
        //返回 自己增的 uid
        return $uid;
    }
    
    

    /**
     * 获取用户信息
     * @param $uid 用户ID或者用户名
     * @param $type 查询方式，1=用户ID，2=用户名，3=手机
     */
    public function get($uid) {
        $uid=trim($uid);
        $type = 1;//用户UID
        //自动判断，由于用户ID基本无法达到100亿（11位数字）级别，所以，认为纯数字类型的和手机号码判断不冲突
        if(verify::verify_uname($uid)=='') $type=2;//用户名登录
        if(verify::verify_mobile($uid)=='') $type=3;//手机号码登录

        if($type==1) {
            if(intval($uid)<=0) return false;
            //$time_start=H :: getmicrotime();
            $userinfo = $this->redis->get_array('user_info:'.$uid);
            //$time_end=H :: getmicrotime();echo('--'.($time_end-$time_start).'<br>');
            if(!$userinfo) return false;
            return $userinfo;
        }

        if($type==2) {
            $login_name = $uid;
            $uid = $this->redis->get('login_name:'.md5($login_name));
            if(!$uid) return false;
            $userinfo = $this->redis->get_array('user_info:'.$uid);
            if(!$userinfo) return false;
            return $userinfo;
        }

        if($type==3) {
            $login_mobile = $uid;

            $uid = $this->redis->get('login_mobile:'.md5($login_mobile));
            if(!$uid) return false;
            $userinfo = $this->redis->get_array('user_info:'.$uid);
            if(!$userinfo) return false;
            return $userinfo;
        }

        return false;
    }

    //更新帐号、手机号、昵称缓存（注册修改去重用）
    function set_user_unique($fields=array(),$timeOut=0) {
        if(!isset($fields['uid'])) return false;
        if(isset($fields['login_name']))
            $this->redis->set('login_name:'.md5($fields['login_name']),$fields['uid'], $timeOut);
        if(isset($fields['login_mobile']))
            $this->redis->set('login_mobile:'.md5($fields['login_mobile']),$fields['uid'], $timeOut);
        if(isset($fields['nick_name']))
            $this->redis->set('nick_name:'.md5($fields['nick_name']),$fields['uid'], $timeOut);
        return true;
    }
    /**
     * 更新用户信息缓存
     * @param $uid 用户ID或者用户名
     * @param $type 查询方式，1=用户ID，2=用户名，3=手机
     */
    function set_user_info($uid){
        $user = $this->dbm->find(DB_DBNAME_USER.".ws_user","*","uid='$uid'");
        $user_vip = $this->dbm->find(DB_DBNAME_USER.".ws_user_vipauth","tou_xian,vip_status,cert_type","uid='$uid'");
        if($user_vip) {
            $user['tou_xian']=$user_vip['tou_xian'];
            $user['vip_status']=$user_vip['vip_status'];
            $user['cert_type']=$user_vip['cert_type'];
        }else{
            $user['tou_xian']='';
            $user['vip_status']=0;
            $user['cert_type']=0;
        }
        if(isset($user['uid']))
        $this->redis->set_array('user_info:'.$user['uid'],$user);
    }
    
    /**
     * 设置用户登录的REDIS SESSION
     * @param $user 用户ID或者用户名
     */
    public function set_session($uid){
        $user=$this->redis->get_array('user_info:'.$uid);
        $session = session::init(); //开启session
        $session_info = array();
        $session_info['login_mobile'] = $user['login_mobile'];
        $session_info['uid'] = $user['uid'];
        $session_info['login_name'] = $user['login_name'];
        $session_info['nick_name'] = $user['nick_name']==''?$user['login_name']:$user['nick_name'];
        $session_info['avatar'] = H::preview_url($user['avatar'],'preview','100_');
        $session->set_array($session_info); //写入用户信息
        return $session_info;
    }
    
    //获取环信用户密码
    public function get_huanxin_pass($uid) {
        $uid = intval($uid);
        if($uid<=0) return '';
        $rs = $this->dbm->find(DB_DBNAME_USER.".ws_user_huanxin",'huanxin_upass',"uid='{$uid}' and reg_status=1");
        if($rs === false) {
            $rs = '';
            //写入环信
            $this->dbm->single_insert(DB_DBNAME_USER.".ws_user_huanxin",array('reg_status'=>0,'uid'=>$uid,'huanxin_upass'=>rand(100000,999999).$uid));
        }
        return $rs;
    }
    
    //判断帐号资料是否完善
    public function account_complete($uid){
        $user=$this->redis->get_array('user_info:'.$uid);
        $user['nick_name']=str_replace(' ','',$user['nick_name']);
        if($user['nick_name']=='') return false;
        if($user['true_name']=='') return false;
        if($user['province']==0) return false;
        if($user['qq']=='') return false;
        if($user['wx']=='') return false;
        return true;
    }
    
    /**
     * 极光推送队列
     * @param $uids array 要发送的目标用户
     * @param $content string 发送内容
     * @param $extras array 通知类型对应的其他参数 $extras=array('type'=>'post/page/product/replyme','id'=>1)
     */
    public function jpush_notify($uids=array(),$content="",$extras=array()){
        if(!is_array($uids)) return;
        foreach($uids as $k=>$v){
            $user=$this->get($v);
            if(isset($user['notify_status']) && $user['notify_status']==0) unset($uids[$k]);
            $uids[$k]=(string)$v;
        }
        if(count($uids)==0) return;
        //if(count($uids)==1) $uids=$uids[0];
        
        $key=md5(json_encode($uids).$content.json_encode($extras));
        $val=array('alias'=>$uids,'content'=>$content,'extras'=>$extras);
        $val=json_encode($val);
        myredis::init()->task_push('jpush_notify', $key, $val);
    }
    
    /**
     * 获取判断用户是否为合伙人身份
     * @param $uid 用户ID或者用户名
     */
    function get_user_partner($uid){
    	$partner = $this->dbm->find(DB_DBNAME_USER.".ws_user_partner","*","uid='$uid'");   	
    	if($partner) {
    		return $partner;		
    	}else{
    		return false;
    	}
    }
	
	//获取用户知识豆
	function getCredit($uid){
		$c = $this->dbm->find(DB_DBNAME_TEACH.'.ws_credit','credits',"uid='{$uid}'");
		if(!$c){return "0";}
		return $c;
	}
	//获取用户知识豆明细
	//uid 用户uid，p分页码
	function getCreditLists($uid,$p){
		$params = array(
				'table_name' => DB_DBNAME_TEACH.'.ws_record',
				'fields'     => 'type,credits,createtime',
				'where'      => "fromuid='{$uid}'",
				'suffix'     => 'order by createtime desc '.$this->dbm->get_limit_sql(PAGESIZE_APP,$p),
				'count'      => 0,
				'pagesize'   => PAGESIZE_APP,
			);
		$lists = $this->dbm->single_query($params);
		return $lists;
	}
	/*判断该用户是否是导师*/
	function isTeacher($uid){
		$t = $this->dbm->find(DB_DBNAME_TEACH.'.ws_teacher','uid,level',"uid='{$uid}'");
		return $t;
	}
	//获取导师信息
	function getTeacherInfo($uid){
		$tmp = $this->dbm->find(DB_DBNAME_TEACH.'.ws_teacher','uid,level,intro,flowers,applause,students,fans,together,good,average,bad,personality,major',"uid='{$uid}'");
		if(!$tmp){return false;}
		if($tmp['level'] == 0){
			$tmp['levelimg'] = "http://test.css.huiweishang.com/wap/img/hhr_ic_copper.png";
		}else if($tmp['level'] == 1){
			$tmp['levelimg'] = "http://test.css.huiweishang.com/wap/img/hhr_ic_gold.png";
		}
		return $tmp;
	}
	/*
	 * 用户积分消费
	 * $fromuid 用户UID
	 * $touid 导师uid
	 * $credit 需要消费的积分
	 * $type 消费类型
	 * $num 数量
	*/
	function changeUserCredit($fromuid = 0,$touid = 0,$credit = 0,$type = 1,$num=0){
		$arr = array('未知','充值','报班','赠送鲜花','赠送掌声','实名认证','拜师','推广神器','上课延时');
		//查询该用户是否有足够的积分
		$ucredit = $this->dbm->find(DB_DBNAME_TEACH.'.ws_credit','credits',"uid='{$fromuid}'");

		if(!$ucredit){
			$r = $this->dbm->single_insert(DB_DBNAME_TEACH.".ws_credit",array('uid'=>$fromuid,'credits'=>0));
		}
		if($type != 1){
			if($ucredit < $credit){die ('{"code":"1","msg":"用户积分不够，请充值","data":""}');}
		}
		//插入用户记录表
		$data = array(
			'fromuid'      => $fromuid,
			'type'         => $type,
			'txt'          => $arr[$type],
			'createtime'   => time(),
			'credits'      => $credit,
			'touid'        => $touid
		);
		$rs = $this->dbm->single_insert(DB_DBNAME_TEACH.".ws_record",$data);
		if($rs['error']){return false;}
		//扣除/增加用户积分
		if ($type == 1){
			//更新用户知识豆
			$sql = "UPDATE ".DB_DBNAME_TEACH.".ws_credit SET credits=credits+".$credit." WHERE uid = ".$fromuid;
		}else {
			$sql = "UPDATE ".DB_DBNAME_TEACH.".ws_credit SET credits=credits-".$credit." WHERE uid = ".$fromuid;
		}
		$rs = $this->dbm->query_update($sql);
		if($rs['error']){return false;}
		if($num == 0){$num = $credit / UNIT_PRICE;}
		if($type == 3){
			//如果是赠送鲜花
			$sql = "UPDATE ".DB_DBNAME_TEACH.".ws_teacher SET flowers=flowers+".$num." WHERE uid = ".$touid;
			$this->dbm->query_update($sql);
		}
		if($type == 4){
			//掌声
			$sql = "UPDATE ".DB_DBNAME_TEACH.".ws_teacher SET applause=applause+".$num." WHERE uid = ".$touid;
			$this->dbm->query_update($sql);
		}
		return true;
	}
	
	
    
}

