<?php
class myredis {
	private $redis; // 数据库连接对象
	private static $redis_obj = null; // 静态实例对象
	
	/**
	 * 初始化类，根据传入的数据库参数连接数据库
	 */
	public function __construct() {
		// $time_start=H :: getmicrotime();
		$this->redis = new Redis ();
		$this->redis->connect ( REDIS_SERVER_HOST, REDIS_SERVER_PORT );
		// $time_end=H :: getmicrotime();echo(($time_end-$time_start).'<br>');
	}
	
	// 单例方法，用户访问实例的公共的静态方法
	public static function init() {
		if (! self::$redis_obj instanceof self) {
			self::$redis_obj = new self ();
		}
		return self::$redis_obj;
	}
	// 创建__clone方法防止对象被复制克隆
	public function __clone() {
		trigger_error ( 'Clone is not allow!', E_USER_ERROR );
	}
	/**
	 * 销毁类
	 */
	public function __destruct() {
		$this->redis->close ();
		unset ( $this->redis );
	}
	
	/**
	 * *****************************验证码缓存方法开始******************************
	 */
	/**
	 *
	 * @param $pool_name 短信验证码池子名称，方便不同验证码用途（如找回密码，更换手机，注册）        	
	 * @param $pre 已发送验证码前缀标识（临时缓存）        	
	 * @param $mobile 手机号码        	
	 * @param $code 验证码        	
	 * @return 无返回
	 */
	function add_sms_pool($pool_name, $pre, $mobile, $code) {
		// $this->redis->set($pool_name,@serialize(array()));
		// if($mobile!='18179130618') return;
		// 如果发送短信还没过期，则不添加
		$redis_code = $this->redis->get ( $pre . ':' . $mobile );
		if ($redis_code) {
			H::logs ( 'redis', $pre . ' 号码 ' . $mobile . ' 验证码 ' . $redis_code . ' 未失效，不再添加发送池' );
			// die('{"code":"1","msg":"短信已经发送，请稍后查看手机","id":"mcode"}');
			return '短信已经发送，请稍后查看手机';
		} else {
			H::logs ( 'redis', $pre . ' 号码发送池增加：' . $mobile . ' ' . $code );
		}
		
		$pool = $this->redis->get ( $pool_name );
		$pool_arr = @unserialize ( $pool );
		// H::logs('redis','池子数组（添加前）'.json_encode($pool_arr));
		
		if ($pool_arr && is_array ( $pool_arr )) {
			// 如果存在，则提示短信正在发送，否则加入池子
			if (isset ( $pool_arr [$mobile] )) {
				H::logs ( 'redis', $pre . ' 验证码发送中' . $mobile );
				// die('{"code":"1","msg":"短信正在发送，请稍后查看手机","id":"mcode"}');
				return '短信正在发送，请稍后查看手机';
			} else {
				$pool_arr [$mobile] = $code;
				$this->redis->set ( $pool_name, @serialize ( $pool_arr ) );
			}
		} else {
			$pool_arr = array (
					$mobile => $code 
			);
			$this->redis->set ( $pool_name, @serialize ( $pool_arr ) );
		}
		H::logs ( 'redis', $pre . ' 池子数组（添加后）' . json_encode ( $pool_arr ) );
	}
	
	/**
	 *
	 * @param $pool_name 短信通知池子名称，方便不同通知用途（//订单重要状态变更）        	
	 * @param $pre 已发送短信前缀标识（临时缓存）        	
	 * @param $mobile 手机号码        	
	 * @param $messge 短信内容        	
	 * @return 无返回
	 */
	function add_sms_pool_order($pool_name, $pre, $mobile, $messge) {
		// $this->redis->set($pool_name,@serialize(array()));
		// if($mobile!='18179130618') return;
		// 如果发送短信还没过期，则不添加
		$redis_code = $this->redis->get ( $pre . ':' . $mobile );
		if ($redis_code) {
			H::logs ( 'redis-order', $pre . ' 号码 ' . $mobile . ' 订单： ' . $redis_code . ' 未失效，不再添加发送池' );
			// die('{"code":"1","msg":"短信已经发送，请稍后查看手机","id":"mcode"}');
			return '短信已经发送，请稍后查看手机';
		} else {
			H::logs ( 'redis-order', $pre . ' 号码发送池增加：' . $mobile . ' ' . $messge );
		}
		
		$pool = $this->redis->get ( $pool_name );
		$pool_arr = @unserialize ( $pool );
		// H::logs('redis','池子数组（添加前）'.json_encode($pool_arr));
		
		if ($pool_arr && is_array ( $pool_arr )) {
			// 如果存在，则提示短信正在发送，否则加入池子
			if (isset ( $pool_arr [$mobile] )) {
				H::logs ( 'redis-order', $pre . ' 订单发送中' . $mobile );
				// die('{"code":"1","msg":"短信正在发送，请稍后查看手机","id":"mcode"}');
				return '短信正在发送，请稍后查看手机';
			} else {
				$pool_arr [$mobile] = $messge;
				$this->redis->set ( $pool_name, @serialize ( $pool_arr ) );
			}
		} else {
			$pool_arr = array (
					$mobile => $messge 
			);
			$this->redis->set ( $pool_name, @serialize ( $pool_arr ) );
		}
		H::logs ( 'redis-order', $pre . ' 池子数组（添加后）' . json_encode ( $pool_arr ) );
	}
	
	/**
	 *
	 * @param $pool_name 短信通知池子名称，方便不同通知用途（//订单重要状态变更）
	 * @param $pre 已发送短信前缀标识（临时缓存）
	 * @param $mobile 手机号码
	 * @param $messge 短信内容
	 * @return 无返回
	 */
	function add_sms_pool_mg($pool_name, $pre, $mobile, $messge) {
		// $this->redis->set($pool_name,@serialize(array()));
		// if($mobile!='18179130618') return;
		// 如果发送短信还没过期，则不添加
		$redis_code = $this->redis->get ( $pre . ':' . $mobile );
		if ($redis_code) {
			H::logs ( 'redis-mg', $pre . ' 号码 ' . $mobile . ' 魔购中奖： ' . $redis_code . ' 未失效，不再添加发送池' );
			// die('{"code":"1","msg":"短信已经发送，请稍后查看手机","id":"mcode"}');
			return '短信已经发送，请稍后查看手机';
		} else {
			H::logs ( 'redis-mg', $pre . ' 号码发送池增加：' . $mobile . ' ' . $messge );
		}
	
		$pool = $this->redis->get ( $pool_name );
		$pool_arr = @unserialize ( $pool );
		// H::logs('redis','池子数组（添加前）'.json_encode($pool_arr));
	
		if ($pool_arr && is_array ( $pool_arr )) {
			// 如果存在，则提示短信正在发送，否则加入池子
			if (isset ( $pool_arr [$mobile] )) {
				H::logs ( 'redis-mg', $pre . ' 魔购中奖发送中' . $mobile );
				// die('{"code":"1","msg":"短信正在发送，请稍后查看手机","id":"mcode"}');
				return '短信正在发送，请稍后查看手机';
			} else {
				$pool_arr [$mobile] = $messge;
				$this->redis->set ( $pool_name, @serialize ( $pool_arr ) );
			}
		} else {
			$pool_arr = array (
					$mobile => $messge
			);
			$this->redis->set ( $pool_name, @serialize ( $pool_arr ) );
		}
		H::logs ( 'redis-mg', $pre . ' 池子数组（添加后）' . json_encode ( $pool_arr ) );
	}
	
	/**
	 * 遍历池子发送短信
	 *
	 * @param $pool_name 池子名称        	
	 * @param $pre 已发送前缀标识（临时缓存）        	
	 */
	function send_sms_pool($pool_name, $pre, $n = 1) {
		// echo('0'.chr(10).chr(13));
		$pool_arr = $this->get_sms_pool ( $pool_name );
		// 发送返回信息
		$msg_list = array ();
		if ($pool_arr && count ( $pool_arr ) > 0) {
			$i = 0; // echo('1'.chr(10).chr(13));
			foreach ( $pool_arr as $k => $v ) { // echo('2'.chr(10).chr(13));
			                                    // 原池子删除
				unset ( $pool_arr [$k] );
				
				$this->redis->set ( $pool_name, @serialize ( $pool_arr ) );
				// echo('3'.chr(10).chr(13));
				// 如果之前发的没生效，则再发短信
				$redis_code = $this->redis->get ( $pre . ':' . $k );
				
				H::logs ( 'redis', $pre . ' 新短信发送' . $k . ':' . $v );
				//发货短信特殊处理
				if (strstr ( $pre, 'notice_' )) {
					//array_push ( $msg_list, $this->send_sms_dxt ( $k, '', $v ) );
					//array_push($msg_list,$this->send_sms_yam($k,'',$v));
                    array_push($msg_list,$this->send_sms_dxt2($k,'',$v));
					H::logs ( 'pool_sms_orsend', $pre . ' 发货通知短信发送' . $k . ':' . $v );
				}else {
					if ($redis_code == false) { // 防止短时间内重复发送短信
						//array_push ( $msg_list, $this->send_sms_dxt ( $k, $v ) );
                        //array_push($msg_list,$this->send_sms_yam($k,$v));
                        array_push($msg_list,$this->send_sms_dxt2($k,$v));
						// 写入单独用于判断验证码的，有延时
						$this->redis->setex ( $pre . ':' . $k, 120, $v );
					} else {
						// echo('5'.chr(10).chr(13));
						H::logs ( 'redis', $pre . ' 短信发送取消' . $k . ':' . $v );
					}					
				}												
				$i ++;
				if ($i >= $n)
					break;
			}
			// echo('6'.chr(10).chr(13));
			H::logs ( 'redis', '池子数组（发送后）' . json_encode ( $pool_arr ) );
		}
		// echo('7'.chr(10).chr(13));
		return '';
	}
	
	/**
	 * 遍历池子发送短信【发货提醒短信通知专用】
	 *
	 * @param $pool_name 池子名称
	 * @param $pre 已发送前缀标识（临时缓存）
	 */
	function send_sms_pool_order($pool_name, $pre, $n = 1) {
		// echo('0'.chr(10).chr(13));
		$pool_arr = $this->get_sms_pool ( $pool_name );
		// 发送返回信息
		$msg_list = array ();
		if ($pool_arr && count ( $pool_arr ) > 0) {
			$i = 0; // echo('1'.chr(10).chr(13));
			foreach ( $pool_arr as $k => $v ) { // echo('2'.chr(10).chr(13));
				// 原池子删除
				unset ( $pool_arr [$k] );
	
				$this->redis->set ( $pool_name, @serialize ( $pool_arr ) );
				// echo('3'.chr(10).chr(13));
				// 如果之前发的没生效，则再发短信
				$redis_code = $this->redis->get ( $pre . ':' . $k );
				if (1) { // 防止短时间内重复发送短信
				//if ($redis_code == false) { // 防止短时间内重复发送短信
					H::logs ( 'redis', $pre . ' 新短信发送' . $k . ':' . $v );
					if (strstr ( $pre, 'notice_' )) {
						//array_push ( $msg_list, $this->send_sms_dxt ( $k, '', $v ) );
						//array_push($msg_list,$this->send_sms_yam($k,'',$v));
                        array_push($msg_list,$this->send_sms_dxt2($k,'',$v));
						H::logs ( 'pool_sms_orsend', $pre . ' 发货通知短信发送' . $k . ':' . $v );
					}else if(strstr ( $pre, 'mg_' )){
						//array_push($msg_list,$this->send_sms_yam($k,'',$v));
                        array_push($msg_list,$this->send_sms_dxt2($k,'',$v));
						H::logs ( 'pool_sms_mgnotice', $pre . ' 魔购中奖通知短信发送' . $k . ':' . $v );
					}else {
						//array_push($msg_list,$this->send_sms_yam($k,$v));
                        array_push($msg_list,$this->send_sms_dxt2($k,$v));
					}
					// 写入单独用于判断验证码的，有延时
					$this->redis->setex ( $pre . ':' . $k, 120, $v );
				} else {					
					H::logs ( 'redis', $pre . ' 短信发送取消' . $k . ':' . $v );
				}
				$i ++;
				if ($i >= $n)
					break;
			}			
			H::logs ( 'redis', '池子数组（发送后）' . json_encode ( $pool_arr ) );
		}	
		return '';
	}
	
	/**
	 * 判断电话号码和验证码是否正确
	 *
	 * @param $pre 已发送前缀标识（临时缓存）        	
	 * @param $mobile 手机号        	
	 * @param
	 *        	@code 验证码
	 * @return 返回 true false
	 */
	function is_true_code($pre, $mobile, $code) {
		$redis_code = $this->redis->get ( $pre . ':' . $mobile );
		H::logs ( 'redis', $pre . ' 验证号码：' . $mobile . ' 提交验证码：' . $code . ' 真验证码：' . $redis_code );
		if ($redis_code == false)
			return false; // 不存在
		if ($redis_code == $code) {
			return true;
		}
		return false;
	}
	
	/**
	 * 取发送池子
	 *
	 * @param $pool_name 池子名称        	
	 * @param
	 *        	返回池子数组
	 * @return 返回数组或者 false
	 */
	function get_sms_pool($pool_name) {
		$pool = $this->redis->get ( $pool_name );
		if (! $pool)
			return false;
		$pool_arr = @unserialize ( $pool );
		return $pool_arr;
	}
	
	/**
	 * 蝶信通短信发送
	 *
	 * @param $phone 手机号码        	
	 * @param $msg 短信内容        	
	 * @return 返回发送结果
	 */
	function send_sms_dxt($phone = '', $msg = '', $msg_auto = '') {
		$userName = SMS_DXT_NAME; // 必选 string 帐号名称
		$userPass = SMS_DXT_PASS; // 必选 string 密码
		$mobile = $phone; // 必选 string 多个手机号码之间用英文“,”分开，最大支持1000个手机号码，同一请求中，最好不要出现相同的手机号码
		$subid = ""; // 选填 string 通道号码末尾添加的扩展号码
		$message = "您的验证码是" . $msg . "，请于60秒内正确输入，如果非本人操作，请忽略本短信"; // 内容
		if ($msg_auto != '')
			$message = $msg_auto;
		
		$url = SMS_DXT_GATEWAY;
		$message = urlencode ( $message );
		$params = 'UserName=' . $userName . '&UserPass=' . $userPass . '&subid=' . $subid . '&Mobile=' . $mobile . '&Content=' . $message;
		
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 3 );
		$data = curl_exec ( $ch );
		curl_close ( $ch );
		if (! $data)
			H::logs ( 'dxtsmsfail', date ( 'y-m-d h:i:s', time () ) . '(北京)短信发送失败 >>> ' . $phone );
		H::logs ( 'redis', '(北京)短信已经发送 >>> ' . $phone );
		return $data;
	}
    

    /**
     * 提交短信
     */
    function send_sms_dxt2($phone = '', $msg = '', $msg_auto = '') {
        $baseUrl = "http://123.57.48.46:28080/HIF12";
        $userId = "huiws";
        $password = "123456";
        $message = "您的验证码是" . $msg . "，请于60秒内正确输入，如果非本人操作，请忽略本短信"; // 内容
        if ($msg_auto != '')
            $message = $msg_auto;
        $message = "【汇微商】".$message;
        $url = $baseUrl . "/mt";
        $data = json_encode ( array (
                'Userid' => $userId,
                'Passwd' => $password,
                'Cli_Msg_Id' => uniqid (),
                'Mobile' => $phone,
                'Content' => $message 
        ) );
        
        $return_content = $this->http_post_data_dxt($url, $data);
        if (! $return_content)
            H::logs ( 'dxtsmsfail_2', date ( 'y-m-d h:i:s', time () ) . '(北京)短信发送失败 >>> ' . $phone );
        H::logs ( 'redis', '(北京)短信已经发送 >>> ' . $phone );
        return $return_content;
    }

    /**
     * 发送post数据
     *
     * @param
     *          $url
     * @param
     *          $data_string
     */
    function http_post_data_dxt($url, $data_string) {
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
                'Accept: application/json',
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen ( $data_string ) 
        ) );
        ob_start ();
        curl_exec ( $ch );
        $return_content = ob_get_contents ();
        ob_end_clean ();
        
        $return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
        return $return_code;
    }
	
	/**
	 * 柚安米共用通短信发送
	 *
	 * @param $phone 手机号码        	
	 * @param $msg 短信内容        	
	 * @return 返回发送结果
	 */
	function send_sms_yam($phone = '', $msg = '', $msg_auto = '') {
		$userName = SMS_YAM_NAME; // 必选 string 帐号名称
		$userPass = SMS_YAM_PASS; // 必选 string 密码
		$mobile = $phone; // 必选 string 多个手机号码之间用英文“,”分开，最大支持1000个手机号码，同一请求中，最好不要出现相同的手机号码
		$message = "您的验证码是" . $msg . "，请于60秒内正确输入，如果非本人操作，请忽略本短信"; // 内容
		if ($msg_auto != '')
			$message = $msg_auto;
		
		$message = iconv ( 'utf-8', 'gbk', $message );
		$url = SMS_YAM_GATEWAY . '?username=' . $userName . '&password=' . $userPass . '&from=001&to=' . $mobile . '&content=' . $message . '&presendTime=&expandPrefix=';
		
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, '' );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 3 );
		$data = curl_exec ( $ch );
		curl_close ( $ch );
		if (! $data)
			H::logs ( 'yamsmsfail', date ( 'y-m-d h:i:s', time () ) . '(深圳)短信发送失败 >>> ' . $phone );
		H::logs ( 'redis', '(深圳)短信已经发送 >>> ' . $phone );
		return $data;
	}
	/**
	 * *****************************验证码缓存方法结束******************************
	 */
	
	/**
	 * *****************************任务队列******************************
	 */
	// 任务入列
	function task_push($task_name, $key, $val) {
		$pool = $this->redis->get ( $task_name );
		$pool_arr = @unserialize ( $pool );
		// H::logs('redis','池子数组（添加前）'.json_encode($pool_arr));
		
		if ($pool_arr && is_array ( $pool_arr )) {
			// 如果存在，则提示短信正在发送，否则加入池子
			if (isset ( $pool_arr [$key] )) {
				H::logs ( 'redis-task', $task_name . ' 任务已经存在' . $val );
				return '任务已存在';
			} else {
				$pool_arr [$key] = $val;
				$this->redis->set ( $task_name, @serialize ( $pool_arr ) );
			}
		} else {
			$pool_arr = array (
					$key => $val 
			);
			$this->redis->set ( $task_name, @serialize ( $pool_arr ) );
		}
		H::logs ( 'redis-task', $task_name . ' 任务数组（添加后）' . json_encode ( $pool_arr ) );
	}
	
	/**
	 * *****************************任务队列******************************
	 */
	
	/**
	 * 设置单个字符串缓存
	 *
	 * @param string $key
	 *        	缓存键名
	 * @param string $val
	 *        	缓存键值
	 * @param int $timeOut
	 *        	设置缓存时间 timeOut=0,为永久缓存
	 * @return 无返回值，通过 get 方法取值验证是否设置成功
	 */
	function set($key, $val, $timeOut = 0) {
		if ($timeOut > 0) {
			$this->redis->setex ( $key, $timeOut, $val );
		} else {
			$this->redis->set ( $key, $val );
		}
	}
	/**
	 * 获取单个字符串缓存数据
	 *
	 * @param $key 缓存键名        	
	 * @return 返回 fasle 或者字符串
	 */
	function get($key) {
		return $this->redis->get ( $key );
	}
	
	/**
	 * 设置数组序例化缓存
	 *
	 * @param string $key
	 *        	KEY名称
	 * @param array $val
	 *        	设值数组
	 * @param int $timeOut
	 *        	设置缓存时间 timeOut=0, 为永久缓存
	 * @return 无返回，通过 get_array 方法验证是否设置成功
	 */
	public function set_array($key, $val, $timeOut = 0) {
		if (is_array ( $val )) {
			$val = @serialize ( $val );
		}
		if ($timeOut > 0) {
			$this->redis->setex ( $key, $timeOut, $val ); // 设置过期时间
		} else {
			$this->redis->set ( $key, $val );
		}
	}
	
	/**
	 * 通过key获取缓存反序例化数组
	 *
	 * @param $key KEY名称        	
	 * @return 返回 false 或者数组
	 */
	public function get_array($key) {
		$result = $this->redis->get ( $key );
		if (! $result)
			return false;
		$result = @unserialize ( $result );
		return $result;
	}
	/**
	 * 将哈希表 $key 中的域 $field 的值设为 $value
	 */
	public function hash_set($key, $field, $value) {
		return $this->redis->hSet ( $key, $field, $value );
	}
	/**
	 * 返回哈希表 $key 中给定域 $field 的值
	 */
	public function hash_get($key, $field) {
		return $this->redis->hGet ( $key, $field );
	}
	/**
	 * 同时将多个 $field->$value (域-值)对设置到哈希表 $key 中
	 */
	public function hash_set_array($key, $arr) {
		return $this->redis->hMset ( $key, $arr );
	}
	/**
	 * 返回哈希表 $key 中，所有的域和值
	 */
	public function hash_get_array($key) {
		return $this->redis->hGetAll ( $key );
	}
	/**
	 * 为 $key 设置生存时间 $time 注意 时间是一个时间戳
	 */
	public function time_out($key, $time) {
		return $this->redis->expireAt ( $key, $time );
	}
	/**
	 * 删除
	 */
	public function del($key) {
		$this->redis->del ( $key );
		H::logs ( 'redis', $key . ' 缓存删除' );
	}
	
	/*
	 * redis入队列
	 * @param $key
	 * @param $value
	 * @$value=需要保存的数据 array(array('name'=>'a1'),array('name'=>'a2'))
	 */
	public function rdlpush($key, $value) {
		try {
			$value = @serialize ( $value );
			return $this->redis->LPUSH ( $key, $value );
		} catch ( Exception $e ) {
			echo $e->getMessage () . "\n";
		}
	}
	
	/*
	 * redis出队列
	 * @param $key
	 * @param $value
	 */
	public function rdlpop($key) {
		//$value = $this->redis->LPOP ( $key );
		//if(empty($value)) return false;
		//$value = @unserialize ( $value );
		//return $value;
		return $this->redis->LPOP ( $key );
	}
	
	/*
	 * redis获取队列长度
	 * @param $key
	 * @param $value
	 */
	public function get_pushlen($key) {
		$len = $this->redis->Llen ( $key );
		if(!intval($len)) return false;
		return $len;
	}
	
	/*
	 * 利用redis防止页面恶意刷新函数
	 */
	function get_rqcount() {
		$patharr = pathinfo ( $_SERVER ['PHP_SELF'] );		
		$filename = explode ( '?', $patharr ['basename'] ); // 获取当前文件名
		
		if (in_array ( $filename ['0'], array (
				'index.php',
				'login.php',
				'password.php' 
		) )) {
			$clientip = get_client_ip ();
			
			// 使用长整型IP和文件名生成KEY			
			$ipkey = 'hws' . sprintf ( "%u", ip2long ( $clientip ) ) . $filename ['0'];			
			if ($visitCount = $this->redis->get ( $ipkey )) {				
				if ($visitCount == 100) {
					H::logs ( 'refresh-large', date('Y-m-d H:i:s').' '.$clientip . ' 频繁刷新页面 '.$visitCount.',请注意了' );					
				} else {					
					$this->redis->incr ( $ipkey );
				}
			} else {				
				$this->redis->set ( $ipkey, 1, 0, 60 );
			}
		}
	}
}

?>