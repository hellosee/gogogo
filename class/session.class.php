<?php
/**
 * 基于redis的session共享类
 * 例子：
 *  session::time_out(86400);  //自定义过期时间
 *   session::set_session_name('ce402505ea25b5540733a7bf9e1642ac'); //指定具体的session name
 *  $session = session::init(); //开启session
 *  @author 李
 */
class session {
    private $redis = ''; //redis 对象
    private static $sessionId = 'RSID';
    private static $sessionName = '';
    private static $expired = 604800; //过期时间
    private static $instance = false;
    private static $folder = 'session:';

    //初始化对象
    private function __construct(myredis $redis) {
        $_COOKIE = H::sqlxss($_COOKIE);
        $this->redis = $redis;
        if(self::$sessionName == '') $this->set_session_name();
        $this->open();
    }
    public static function init() {
        if(!self::$instance) self::$instance = new self(myredis::init());
        return self::$instance;
    }
    public function __call( $method, $params ) {
        return call_user_func_array(array(&$this->redis,$method),$params);
    }
    //创建__clone方法防止对象被复制克隆
    public function __clone(){
        trigger_error('Clone is not allow!',E_USER_ERROR);
    }
    /** 销毁session
     */
    public function destroy($sessionName='') {
        $this->set_cookie(self::$sessionId,'',time()-100,'/',HTTP_HOST);
        $sessionName = $sessionName == '' ? self::$sessionName : $sessionName;
        $this->redis->time_out(self::$folder.$sessionName,time()-100);
    }
    /**延长session有效时间
     */
    public function delay() {
        if($this->is_open() == true) {
            $time = time() + self::$expired;
            $this->set_cookie(self::$sessionId,self::$sessionName,$time,'/',HTTP_HOST);
            $this->redis->time_out(self::$folder.self::$sessionName,$time); //设置过期时间
        }
    }
    /** 获取session的cookie键名
     */
    public function get_session_id() {
        return self::$sessionId;
    }
    /** 获取session的cookie的值
     */
    public function get_session_name() {
        return self::$sessionName;
    }
    /** 设置session_name 可以自定义
     */
    public static function set_session_name($sessionName='') {
        if($sessionName) { //自定义session name 删除cookie中已有的值
            self::$sessionName = $sessionName;
        }elseif(isset($_COOKIE[self::$sessionId])) {
            self::$sessionName = $_COOKIE[self::$sessionId];
        }else{ //动态生成 session name 删除cookie中已有的值
            self::$sessionName = md5(uniqid(rand(), true));
        }
    }
    /** 过期时间
     */
    public static function time_out($expired) {
        self::$expired = $expired;
    }
    /** 开启session
     */
    private function open() {
        if($this->is_open() == false) {
            $time = time() + self::$expired;
            $this->set_cookie(self::$sessionId,self::$sessionName,$time,'/',HTTP_HOST);
            $this->hash_set_array(self::$folder.self::$sessionName,array(0=>0)); //设置初始值
            $this->redis->time_out(self::$folder.self::$sessionName,$time); //设置过期时间
        }
    }
    /** 判断是否开启session
     */
    private function is_open() {
        if(!isset($_COOKIE[self::$sessionId]) || $_COOKIE[self::$sessionId] != self::$sessionName) return false;
        return true;
    }
    /**
     * 写入cookie
     *
     * @param  $var 键
     * @param  $value 值
     * @param  $time 过期时间 单位秒
     */
    private function set_cookie($var,$value='',$time=0,$path='/',$domain=''){
        $_COOKIE[$var] = $value;
        if(is_array($value)){
            foreach($value as $k=>$v){
                if(is_array($v)){
                    foreach($v as $a=>$b){
                        setcookie($var.'['.$k.']['.$a.']',$b,$time,$path,$domain);
                    }
                }else{
                    setcookie($var.'['.$k.']',$v,$time,$path,$domain);
                }
            }
        }else{
            setcookie($var,$value,$time,$path,$domain);
        }
    }
    /* 返回单所有值 失败 返回 false
    */
    public function get_array() {
        if($this->is_open() == false) return false;
        $tmp = $this->hash_get_array(self::$folder.self::$sessionName);
        if(isset($tmp[0])) unset($tmp[0]);
        if(count($tmp) == 0) return false;
        return $tmp;
    }
    /** 设置一个一维数组 失败返回 false
     */
    public function set_array($arr) {
        if(!is_array($arr) || count($arr) == 0) return false;
        if($this->is_open() == false) return false; //没有开启session
        $this->hash_set_array(self::$folder.self::$sessionName,$arr);
    }
    /* 返回单个值 失败 返回 false
    */
    public function get($key) {
        if($this->is_open() == false) return false;
        return $this->hash_get(self::$folder.self::$sessionName,$key);
    }
    /** 设置单个值 失败 返回 false 成功 返回数字
    */
    public function set($key,$value) {
        if($this->is_open() == false) return false;
        return $this->hash_set(self::$folder.self::$sessionName,$key,$value);
    }
    /** 判断用户是否登录 登录==true 未登录==false
     */
    public function is_login() {
        if(is_numeric($this->get('uid'))) return true;
        return false;
    }

}



