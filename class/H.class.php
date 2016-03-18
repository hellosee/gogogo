<?php
class H {
/**
 * 可逆加密解密的公钥，不能出现重复字符，内有A-Z,a-z,0-9,/,=,+,_,-
 */
public static $lockstream = 'st=lDEFABCkVWXYZabc89LMmGH012345uvdefIJK6NOPyzghijQRSTUwx7nopqr';

//==========================文本处理方法==========================

/**
 * 中文字符截取
 *
 * @param  $str 要截取字符串
 * @param  $start 开始位置
 * @param  $length 长度
 */
public static function utf8_substr($str, $start, $length) {
    if (function_exists('mb_substr')) {
        return mb_substr($str, $start, $length, 'UTF-8');
    }
    preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $str, $arr);
    return implode("", array_slice($arr[0], $start, $length));
}
/**
 *  解析 文件路径 返回其组成部分
 *
 * @param  $str 要解析的路径 例如：/a/b/test.php
 * @param  $type 返回类型 all=返回全部 oname=返回文件名 ext=返回文件后缀 path=返回不包含文件名的文件路径
 */
public static function parse_path($str,$type='all') {
    $start = strripos($str,'/');
    if($start !== false) $start += 1;
    $end = strripos($str,'.');
    if($end <= 0) return false;
    $oname = substr($str,$start,$end - $start);
    $ext = substr($str,$end+1);
    $path = substr($str,0,$start);
    if($type == 'oname') return $oname;
    if($type == 'ext') return $ext;
    if($type == 'path') return $path;
    return array('oname'=>$oname,'ext'=>$ext,'path'=>$path);
}

/**
 * 字符串防SQL注入编码，对GET,POST,COOKIE的数据进行预处理
 *
 * @param  $input 要处理字符串或者数组
 * @param  $urlencode 是否要URL编码
 */
public static function escape($input, $urldecode = 0) {
    if(is_array($input)){
        foreach($input as $k=>$v){
            $input[$k]=H::escape($v,$urldecode);
        }
    }else{
        $input=trim($input);
        if ($urldecode == 1) {
            $input=str_replace(array('+'),array('{addplus}'),$input);
            $input = urldecode($input);
            $input=str_replace(array('{addplus}'),array('+'),$input);
        }
        // PHP版本大于5.4.0，直接转义字符
        if (strnatcasecmp(PHP_VERSION, '5.4.0') >= 0) {
            $input = addslashes($input);
        } else {
            // 魔法转义没开启，自动加反斜杠
            if (!get_magic_quotes_gpc()) {
                $input = addslashes($input);
            }
        }
    }
    //防止最后一个反斜杠引起SQL错误如 'abc\'
    if(substr($input,-1,1)=='\\') $input=$input."'";//$input=substr($input,0,strlen($input)-1);
    return $input;
}


/**
 * 处理XSS，$input=$_COOKIE,$_GET,$_POST
 */
public static function sqlxss($input){
    if(is_array($input)){
        foreach($input as $k=>$v){
            $k=H::sqlxss($k);
            $input[$k]=H::sqlxss($v);
        }
    }else{
        $input=H::escape($input,1);
        $input=htmlspecialchars($input,ENT_QUOTES);
    }
    return $input;
}
public static function sqlxss_decode($input){
    if(is_array($input)){
        foreach($input as $k=>$v){
            $input[$k]=H::sqlxss_decode($v);
        }
    }else{
        //$input=H::escape($input,1);
        $input=htmlspecialchars_decode($input,ENT_QUOTES);
    }
    return $input;
}
/**
 * 字符串去反斜杠处理，模板编辑源码时候需要使用
 *
 * @param  $str 反斜杠
 */
public static function escape_stripslashes($str) {
    $str=trim($str);
    // PHP版本大于5.4.0，直接转义字符
    if (strnatcasecmp(PHP_VERSION, '5.4.0') < 0) {
        // 魔法转义没开启，自动加反斜杠
        if (get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }
    }
    return $str;
}
/**
 * 过滤HTML为纯TXT，并且可截取长度
 * @param $input 字符串或者数组
 * @param $len 截取长度，0为不截取
 * @param $filter 数组不过滤的元素名数组
 * 
 */
public static function filter_txt($input,$len=0,$filter=array()){
    if(is_array($input)){
        foreach($input as $k=>$v){
            if(!in_array($k,$filter)) {
                $input[$k] = H::filter_txt($v, $len,$filter);
            }
        }
    }else{
        $input=H::escape($input,1);
        $input=H::sqlxss_decode($input);//解码成HTML
        $input=strip_tags($input);
        $input=str_replace(array('　',' ','"'),'',$input);
        if($len>0) $input=H::utf8_substr($input,0,$len);
        $input=trim($input);
    }
    return $input;
}

/**
 * 过滤HTML为纯TXT，并且可截取长度
 * @param $input 字符串
 * @param $len 截取长度，0为不截取
 */
public static function filter_desc($str,$len=30) {
    $str = stripcslashes(H::sqlxss_decode($str));
    $str = strip_tags($str);
    $str = str_replace(array(' ','　',chr(10),chr(13),',','&nbsp;','&amp;','&ldquo;','&rdquo;','&amp;'),array('','','','','，','','','“','”','&'),$str);
    $str = H::utf8_substr($str,0,$len);
    return H::sqlxss($str);
}


/**
 * app 输出预处理函数
 */
public static function filter_html_app($con) {
    $tags = '<br><p>'; //保留标签
    $con = strip_tags($con,$tags); //去除不被保留的标签
    //处理P标签
    $con=preg_replace('~<p[^>]*>~','<p>',$con);
    //处理BR标签
    $con=preg_replace('~<br[^>]*>~','<br>',$con);
    //处理IMG标签
    $con=preg_replace('~<img([^>]*)( src=[^>]* )([^>]*)>~','<img${2}>',$con);
    
    //替换为文本换行
    $con=preg_replace('~<p></p>~','\r\n',$con);
    $con=preg_replace('~<p>~','\r\n',$con);
    $con=preg_replace('~</p>~','\r\n',$con);
    $con=preg_replace('~<br>~','\r\n',$con);
    
    return $con;
}
public static function filter_html_app_desc($con) {
    $tags = '<br><p><img>'; //保留标签
    $con = strip_tags($con,$tags); //去除不被保留的标签
    //处理P标签
    $con=preg_replace('~<p[^>]*>~','<p>',$con);
    //处理BR标签
    $con=preg_replace('~<br[^>]*>~','<br>',$con);
    //处理IMG标签
    $con=preg_replace('~<img([^>]*)( src=[^>]* )([^>]*)>~','<img${2}>',$con);
    
    //替换为文本换行
//  $con=preg_replace('~<p></p>~','\r\n',$con);
//  $con=preg_replace('~<p>~','\r\n',$con);
//  $con=preg_replace('~</p>~','\r\n',$con);
//  $con=preg_replace('~<br>~','\r\n',$con);
    
    return $con;
}
/**
 * 返回图片缩略图地址
 * @param url 缩略图、预览文件地址地址
 * @param type 返回地址类型，默认为 preview
 * @param $prefix 文件名前缀
 */
public static function preview_url($url,$type='preview',$prefix='') {
	if($url == '') return 'noimg';
	if(strstr($url,'http://css')) return $url;
    switch($type){
        case 'same_dir':
            $pos = strrpos($url, '/');
            $url = substr($url, 0, $pos + 1) . 'thumb_' . substr($url, $pos + 1, strlen($url) - $pos);
        default:
            $host=parse_url($url, PHP_URL_HOST);

            if($host==''){

            }else{
                $url=substr($url,7);//去掉HTTP头
                $url_arr=explode('/',$url);//分割数组
                //去除空的
                foreach($url_arr as $m=>$n){
                    if($n=='') unset($url_arr[$m]);
                }
                array_splice($url_arr,2,0,$type);//合并插入$type=preview目录

                //加入文件名前缀
                if($prefix!='') {
                    $url_arr[count($url_arr)-1]=$prefix.$url_arr[count($url_arr)-1];                }
                    $url='http://'.implode('/',$url_arr);//die($url);
            }
    }
    //die($url);
    return $url;
}
/**
 * 替换URL中GET参数
 * @url 缩略图地址
 * @params 要替换的参数列表 array('id'=>123,'type'=>1)
 */
public static function url_params($url,$params=array(),$params_filter=array('p')) {
    $a=parse_url($url);
    $a['query']=isset($a['query'])?$a['query']:'';
    $b=explode('&',$a['query']);//得到参数
    $c=array();
    foreach($b as $k=>$v){//拆解重组参数
        $tmp=explode('=',$v);
        if(count($tmp)>1){
            $c[$tmp[0]]=$tmp[1];
        }else{
            if($tmp[0]!='') $c[$tmp[0]]='';
        }
    }

    foreach($params as $k=>$v){//替换参数
        $c[$k]=$v;
    }
    //回拼字符串
    $d=array();
    foreach($c as $k=>$v){
        if(in_array($k,$params_filter)) continue;
        array_push($d,$k.'='.$v);
    }
    $query=implode('&',$d);
    $url=(isset($a['path'])?$a['path']:'').($query==''?'':'?'.$query);
    return $url;
}
//==========================数组处理方法==========================

/**
 * JSON_ENCODE中文不编码，显示纯中文
 */
public static function json_encode_ch($a = false) {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a)) {
        if (is_float($a)) {
            // Always use "." for floats.
            return floatval(str_replace(",", ".", strval($a)));
        }

        if (is_string($a)) {
            static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
            return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
        } else {
            return $a;
        }
    }

    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
        if (key($a) !== $i) {
            $isList = false;
            break;
        }
    }

    $result = array();
    if ($isList) {
        foreach ($a as $v) $result[] = H::json_encode_ch($v);
        return '[' . join(',', $result) . ']';
    } else {
        foreach ($a as $k => $v) $result[] = H::json_encode_ch($k).':'.H::json_encode_ch($v);
        return '{' . join(',', $result) . '}';
    }
}
/**
 * 二维数组排序
 *
 * @param  $arr 数组
 * @param  $keys 排序字段
 * @param  $type 升序降序
 */
public static function array_sort($arr, $keys, $type = 'asc') {
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ($type == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
        $new_array[$k] = $arr[$k];
    }
    return $new_array;
}
/**
 * 将二维数组的某个字符串维度转换为数字
 *
 * @param  $arr 要转换的数组
 * @param  $col_name 要转换的列名
 */
public static function ver_sort($arr, $col_name) {
    for($i = 0;$i < count($arr);$i++) {
        $arr[$i]['new_order_set'] = H :: get_str_num($arr[$i][$col_name]);
    }
    // 排序
    $arr_tmp = H :: array_sort($arr, 'new_order_set', 'desc');

    $ret = array();
    foreach($arr_tmp as $k => $v) {
        unset($v['new_order_set']);
        array_push($ret, $v);
    }
    return $ret;
}
/**
 * 数组变成字符串
 * @param $array 数组
 * @level 深度
 */
public static function array_eval($array, $level = 0) {
    $space = '';
    $str_t = '\t';
    $str_t = '';
    $str_n = '\n';
    $str_n = '';
    for($i = 0; $i <= $level; $i++) {
        $space .= $str_t;
    }
    $evaluate = "array" . $str_n . "$space(" . $str_n;
    $comma = $space;
    foreach($array as $key => $val) {
        $key = is_string($key) ? '\'' . addcslashes($key, '\'\\') . '\'' : $key;
        //$val = !is_array($val) && (!preg_match("/^\-?\d+$/", $val) || strlen($val) > 12) ? '\'' . addcslashes($val, '\'\\') . '\'' : $val;
        if (is_array($val)) {
            $evaluate .= "$comma$key=>" . H::array_eval($val, $level + 1);
        } else {
            $val='\'' . addcslashes($val, '\'\\') . '\'';
            $evaluate .= "$comma$key=>$val";
        }
        $comma = "," . $str_n . "$space";
    }
    $evaluate .= $str_n . "$space)";
    return $evaluate;
}


//==========================HTTP处理方法==========================

/**
 * 三次重试，获取指定url的内容
 *
 * @param  $url URL地址或者本地文件物理地址
 * @param  $charset 文件编码
 */
public static function get_contents($url, $charset = 'UTF-8') {
    $retry = 3;
    $content = '';
    while (empty($content) && $retry > 0) {
        $content = @file_get_contents($url);
        $retry--;
    }
    if (strtoupper($charset) != 'UTF-8') $content = iconv($charset . "//IGNORE", "UTF-8", $content); //die($contents);
    return $content;
}
/**
 * curl POST
 *
 * @param   string  url
 * @param   array   数据
 * @param   int     请求超时时间
 * @param   bool    HTTPS时是否进行严格认证
 * @return  string
 */
public static function curl_post($url, $data = array(), $timeout = 30, $CA = false){
    if($url=='') return '';
    $cacert = getcwd() . '/cacert.pem'; //CA根证书
    $SSL = substr($url, 0, 8) == "https://" ? true : false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch,CURLOPT_USERAGENT, 'APP=HUIWEISHANG');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout-2);
    if ($SSL && $CA) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   // 只信任CA颁布的证书
        curl_setopt($ch, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
    } else if ($SSL && !$CA) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 检查证书中是否设置域名
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //避免data数据过长问题
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //data with URLEncode

    $ret = curl_exec($ch);
    $error=curl_error($ch);  //查看报错信息
    if(!empty($error)) print_r($error);
    curl_close($ch);
    return $ret;
}

/**
 * 写入cookie
 *
 * @param  $var 键
 * @param  $value 值
 * @param  $time 过期时间 单位秒
 */
public static function set_cookie($var,$value='',$time=0,$path='/',$domain=''){
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

/**
 * 文件存储随机目录，避免一个目录文件过多
 *
 * @param  $type 目录类型 0=默认
 */
public static function rnd_save_path($type=''){
    $ret='';
    $str='0123456789abcdefghijklmnopqrstuvwxyz';
    switch($type){
        case 1:
            $ret=date('Y').'/'.date('m').'/'.date('d').'/';
            break;
        case 2:
            $ret=date('Ym').'/'.date('d').'/';
            break;
        default:
            $ret=date('Ym').'/'.date('d').'/';
    }
    return $ret;
}



//==========================IP操作==========================

/**
 * 获取客户端IP地址
 */
public static function getip() {
    $onlineip = '';
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $onlineip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $onlineip = getenv('REMOTE_ADDR');
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $onlineip = $_SERVER['REMOTE_ADDR'];
    }
    if(!@ereg("^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$",$onlineip)) {
        return "";
    }else{
        return addslashes(htmlspecialchars($onlineip));
    }
}

//==========================分页处理==========================

/**
 * 分页条输出，兼容确定量参数和不确定量
 *
 * @param  $params =array('total'=>0,'pagesize'=>20,'rewrite'=>0,'rule'=>array('node'=>'','obj'=>'','params'=>''));
 * @param total $ 总记录数 必填
 * @param pagesize $ 分页大小，必填
 * @param rewrite $ 0=动态地址，适用于参数数量确定的情况； 1=伪静态地址，首页去除 p 参数； 2=常规动态地址，适用于参数数量不确定的情况，如果rewrite 为 2，则只需要写 total 和 pagesize 和 rewrite 3个参数
 * @param  $rule -> node 伪静态规则节点
 * @param  $rule -> obj 伪静态实例对象
 * @param  $rule -> params 确定的参数值对数组
 */
public static function pagebar($params) { // print_r($params);
    if (!is_array($params)) return '分页码HTML函数参数调用错误，必须是数组';
    if (!isset($params['total'])) $params['total'] = 0;
    if (!isset($params['pagesize'])) $params['pagesize'] = 10;
    if (!isset($params['rewrite'])) $params['rewrite'] = 0; //print_r($params);die();
    // if($params['total']==0) return '';
    //限制输出过多页面
    if($params['total']>$params['pagesize']*200) $params['total']=$params['pagesize']*200;
    if (isset($params['rule'])) {
        $rewrite_obj = $params['rule']['obj']; //URL重写对象
        $node = $rewrite_obj -> url_config[$params['rule']['node']]; //当前节点
        $node_params = $params['rule']['params'];
        // print_r($node_params);
    }else{
        //$params['rule']['obj']=new url_rewrite($params);
    }
    // 如果rewrite模式为2，则直接返回常规动态分页，否则继续过滤分页参数后的分页
    // print_r($params);
    if ($params['rewrite'] == 2) return H :: pagehtml($params['total'], $params['pagesize']);
    //print_r($params);die();
    $params['rule']['obj'] -> rewrite = $params['rewrite'];

    $page_name = 'p'; //分页参数名称
    $_page = !isset($_GET[$page_name]) || intval($_GET[$page_name]) == 0?1:intval($_GET[$page_name]);
    $_pages = ceil($params['total'] / $params['pagesize']); //计算总分页
    $_html = '';
    // 判断循环起止数字
    if ($_pages <= 6) {
        $_pstart = 1;
        $_pend = $_pages;
    } else {
        if ($_page <= 4) {
            $_pstart = 1;
            $_pend = 6;
        } else {
            $_pstart = $_page-3;
            if ($_pstart <= 0) $_pstart = $_page-1;
            if ($_pstart <= 0) $_pstart = $_page;
            $_pend = $_pstart + 5;
            if ($_pend > $_pages) $_pend = $_pages;
        }
    }
    $pagearr=array();
    // 第一页导航
    if ($_pstart > 1) {
        $node_params[$page_name] = 1;
        $_nurl = $params['rule']['obj'] -> encode($params['rule']['node'] . '_index', $node_params);
        $_html .= '<a href="' . $_nurl . '">1...</a>';
        array_push($pagearr,array('txt'=>'1','url'=>$_nurl));
    }
    // 中间循环
    for($_i = $_pstart;$_i <= $_pend;$_i++) {
        if ($_i == $_page) {
            $_html .= '<span class="now_class">' . $_i . '</span>';
            array_push($pagearr,array('txt'=>$_i,'url'=>''));
        } else {
            $node_params[$page_name] = $_i;
            if ($_i == 1) {
                $_nurl = $params['rule']['obj'] -> encode($params['rule']['node'] . '_index', $node_params);
            } else {
                $_nurl = $params['rule']['obj'] -> encode($params['rule']['node'], $node_params);
            }
            $_html .= '<a href="' . $_nurl . '">' . $_i . '</a>';
            array_push($pagearr,array('txt'=>$_i,'url'=>$_nurl));
        }
    }
    // 最末页导航
    if ($_page < $_pages && $_pend < $_pages) {
        $node_params[$page_name] = $_pages;
        $_nurl = $params['rule']['obj'] -> encode($params['rule']['node'], $node_params);
        $_html .= '<a href="' . $_nurl . '">' . $_pages . '</a></span>';
        array_push($pagearr,array('txt'=>$_pages,'url'=>$_nurl));
    }
    if ($_pages == 0) $_page = 0;
    $_html .= '<span class="ptpage"> ' . $_page . '/' . $_pages . ' 页</span>';
    $pagevars = array('totalpage' => $_pages,'pagearr'=>$pagearr, 'pagecode' => $_html, 'pagesize' => $params['pagesize'], 'total' => $params['total'], 'offset' => ($_page-1) * $params['pagesize']);
    // print_r($pagevars);
    return $pagevars;
}
/**
 * 常规分页条，私有方法
 *
 * @param  $_total 记录总数
 * @param  $_pagesize 分页大小
 */
public static function pagehtml($_total = 0, $_pagesize = 20) {
	global $V;
    // die($_total.$_pagesize);
    // if($_total==0) return '';
    $page_name = 'p'; //分页参数名称
    $_page = !isset($_GET[$page_name]) || intval($_GET[$page_name]) == 0?1:intval($_GET[$page_name]);

    $_pagesize = intval($_pagesize);
    $_pages = ceil($_total / $_pagesize); //计算总分页

    $_html = '';
    // 判断循环起止数字
    if ($_pages <= 6) {
        $_pstart = 1;
        $_pend = $_pages;
    } else {
        if ($_page <= 4) {
            $_pstart = 1;
            $_pend = 6;
        } else {
            $_pstart = $_page-3;
            if ($_pstart <= 0) $_pstart = $_page-1;
            if ($_pstart <= 0) $_pstart = $_page;
            $_pend = $_pstart + 5;
            if ($_pend > $_pages) $_pend = $_pages;
        }
    }
    $pagearr=array();
    // 第一页导航
    if ($_pstart > 1) {
        $_nurl = H :: url_replace(1, $page_name);
        $_html .= '<a href="' . $_nurl . '">1...</a>';
        array_push($pagearr,array('txt'=>'1','url'=>$_nurl));
    }
    // 中间循环
    for($_i = $_pstart;$_i <= $_pend;$_i++) {
        if ($_i == $_page) {
            $_html .= '<span class="now_class">' . $_i . '</span>';
            array_push($pagearr,array('txt'=>$_i,'url'=>''));
        } else {
            $_nurl = H :: url_replace($_i, $page_name);
            $_html .= '<a href="' . $_nurl . '">' . $_i . '</a>';
            array_push($pagearr,array('txt'=>$_i,'url'=>$_nurl));
        }
    }
    // 最末页导航
    if ($_page < $_pages && $_pend < $_pages) {
        $_nurl = H :: url_replace($_pages, $page_name);
        $_html .= '<a href="' . $_nurl . '">' . $_pages . '</a>';
        array_push($pagearr,array('txt'=>$_pages,'url'=>$_nurl));
    }
    if ($_pages == 0) $_page = 0;
    //$_html .= '<span class="ptpage"> ' . $_pages . ' 页 / ' . $_total . ' 记录</span>';

    $set_pagesize = $V->input_str(array('node'=>'pagesize','on'=>'onchange="set_pagesize()"','type'=>'select','default'=>$_pagesize,'style'=>'style="width:100px;"'));
    //$_html .= $set_pagesize;
	$_html .= "<span style='background:none;color:#888;border:0;'>共 ".$_total." 条 / ". $_pages." 页</span>";
    $pg_prev = $_page <= 1 ? '' : '<a href="'.H :: url_replace(($_page-1), $page_name).'" class="pg_prev">&lt;</a>';
    $pg_next = $_page >= $_pages ? '' : '<a href="'.H :: url_replace(($_page+1), $page_name).'" class="pg_next">&gt;</a>';

    $pagevars = array('set_pagesize'=>$set_pagesize,'totalpage' => $_pages,'pg_prev'=>$pg_prev,'pg_next'=>$pg_next,'pagearr'=>$pagearr, 'pagecode' => $_html, 'pagesize' => $_pagesize, 'total' => $_total, 'offset' => ($_page-1) * $_pagesize);
    return $pagevars;
}
/**
 * URL页码替换，仅用于常规分页条使用,私有方法
 *
 * @param  $p 页码
 * @param  $get 替换分页参数名
 */
private static function url_replace($p, $get) {
    $url = $_SERVER['REQUEST_URI'];
    if (preg_match('~(&' . $get . '=\d+)~', $url)) {
        return preg_replace('~(&' . $get . '=\d+)~', '&' . $get . '=' . $p, $url);
    } else if (preg_match('~(\?' . $get . '=\d+)~', $url)){
        return preg_replace('~(\?' . $get . '=\d+)~', '?' . $get . '=' . $p, $url);
    } else if (!stristr($url, '?')) {
        return $url . '?' . $get . '=' . $p;
    } else {
        return $url . '&' . $get . '=' . $p;
    }
}

//==========================时间处理==========================

/**
 * 返回时间，单位是毫秒 ms
 */
public static function getmicrotime() {
    list($usec, $sec) = explode(" ", microtime());
    $tim = ((float)$usec + (float)$sec) * 1000;
    return $tim;
}

/**
 * 根据时间戳返回距现在的秒，分钟，小时
 *
 * @param  $stamp 时间戳
 */
public static function datef($stamp,$str='Y-m-d H:i:s') {
    $time_add = time() - $stamp;
    if ($time_add < 60) return $time_add . ' 秒前';
    if ($time_add >= 60 and $time_add < 60 * 60) return intval($time_add / 60) . ' 分钟前';
    if ($time_add >= 60 * 60 and $time_add < 60 * 60 * 12) return intval($time_add / (60 * 60)) . ' 小时前';
    return date($str, $stamp);
}

//==========================加密解密==========================
/**
 * 密码加密方式1
 *
 * @param  $string 要加密字符串
 */
public static function password_encrypt($string) {
    $string = md5(md5(md5($string)));
    return $string;
}
/**
 * 密码加密方式2
 *
 * @param  $str 要加密字符串
 */
public static function password_encrypt_net($str) {
    return H :: md5_net(H :: md5_net(H :: md5_net($str)));
}

/**
 * 可逆加密
 *
 * @param  $txtStream 要加密的字符串
 * @param  $password 加密私钥=解密私钥
 */
public static function encrypt($txtStream, $password) {
    // 随机找一个数字，并从密锁串中找到一个密锁值
    $lockstream=defined('LOCK_STREAM')?LOCK_STREAM:(self :: $lockstream);
    $lockLen = strlen($lockstream);
    $lockCount = rand(0, $lockLen-1);
    $randomLock = $lockstream[$lockCount];
    // 结合随机密锁值生成MD5后的密码
    $password = md5($password . $randomLock);
    // 开始对字符串加密
    $txtStream = base64_encode($txtStream);
    $tmpStream = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txtStream); $i++) {
        $k = $k == strlen($password) ? 0 : $k;
        $j = (strpos($lockstream, $txtStream[$i]) + $lockCount + ord($password[$k])) % ($lockLen);
        $tmpStream .= $lockstream[$j];
        $k++;
    }
    return $tmpStream . $randomLock;
}
/**
 * 可逆解密
 *
 * @param  $txtStream 要解密的字符串
 * @param  $password 解密私钥=加密私钥
 */
public static function decrypt($txtStream, $password) {
    $lockstream=defined('LOCK_STREAM')?LOCK_STREAM:(self :: $lockstream);
    $lockLen = strlen($lockstream);
    // 获得字符串长度
    $txtLen = strlen($txtStream);
    // 截取随机密锁值
    $randomLock = $txtStream[$txtLen - 1];
    // 获得随机密码值的位置
    $lockCount = strpos($lockstream, $randomLock);
    // 结合随机密锁值生成MD5后的密码
    $password = md5($password . $randomLock);
    // 开始对字符串解密
    $txtStream = substr($txtStream, 0, $txtLen-1);
    $tmpStream = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txtStream); $i++) {
        $k = $k == strlen($password) ? 0 : $k;
        $j = strpos($lockstream, $txtStream[$i]) - $lockCount - ord($password[$k]);
        while ($j < 0) {
            $j = $j + ($lockLen);
        }
        $tmpStream .= $lockstream[$j];
        $k++;
    }
    return base64_decode($tmpStream);
}
/**
 * 密码加密方式3，配合security_code方法使用，先生成安全码，再根据安全码生成密码
 * @param string $password 密码
 * @param string $salt 安全码
 */
public static function password_encrypt_salt($password,$salt){
    return md5(md5($password).$salt);
}
/**
 * 生成登录安全码
 */
public static function security_code($length = 8,$type = '') {
    $source = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^&*()_+-=';
    if($type=='number') $source='0123456789';
    if($type=='numstr') $source='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($source);
    $return = '';
    for ($i = 0; $i < $length; $i++) {
        $index = rand() % $len;
        $return .= substr($source, $index, 1);
    }
    return $return;
}

//==========================其他杂项方法==========================

/**
 * 根据UserAgent检查用户浏览设备
 * @return pc 默认为PC，wap 手机  wx 微信
 */
public static function user_dev() {
    $dev='pc';
    $regex_match = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
    $regex_match .= "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|meizu|miui|ucweb";
    $regex_match .= "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";
    $regex_match .= "symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
    $regex_match .= "jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";
    $regex_match .= ")/i";

    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE']) || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT'])))) {
        $dev='wap';
    }
    if(isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
        $dev='wx';
    }
    //判断是否COOKIE设置了当前浏览设备
    //setcookie('mcms_device','wap',time()-3600,'/');
    if(isset($_COOKIE['mcms_device']) && $_COOKIE['mcms_device']!='') return $_COOKIE['mcms_device'];//echo($_COOKIE['mcms_device']);
    return $dev;
}

/**
 * 记录文本日志，如果根目录有 logs 目录才会记录
 *
 * @param  $logs_type 日志类型，日志文件名称
 * @param  $logs_txt 日志内容
 */
public static function logs($logs_type, $logs_txt) {
    // 创建缓存目录
    if(!is_dir(dirname(__FILE__) . '/../logs/')) return;
    try {
        $fp = fopen(dirname(__FILE__) . '/../logs/' . $logs_type . '_' . date('Y_m_d') . '.txt', 'a');
        //chmod("test.txt",0600);
        //chown(dirname(__FILE__) . '/../logs/' . $logs_type . '_' . date('Y_m_d') . '.txt',"www");
        //chmod(dirname(__FILE__) . '/../logs/' . $logs_type . '_' . date('Y_m_d') . '.txt',0777);
        fwrite($fp, date('Y-m-d H:i:s') . ' ' . H :: getip() . ' ' . $logs_txt . ' ' . chr(10));
        fclose($fp);
    }
    catch(Exception $e) {
        echo($e -> getMessage());
    }
}

/**
 * 重定向出错页面
 * @param $data 出错信息JSON对象 {"code":1,"msg":"没有权限","tpl":"/app_tpl_pc/default/error/tpl.show.php"}
 * return 无返回值
 */
public static function error_show($data){
    global $global_global,$C,$V,$U,$T,$Q,$N,$P,$dbm;
    $data_str=$data;
    if(AJAX==1) die($data);
    $data=json_decode($data,1);

    if(isset($data['ajax']) && $data['ajax']==1) {
        if(isset($dbm)) unset($dbm);
        die($data_str);
    }

    require_once(ROOT_PATH_SITE . '/app/error/show.php');
    if(isset($dbm)) unset($dbm);
    die();
}


//==========================百度地图和GPS地理位置==========================

/**
 * 根据地址获取百度坐标
 * @param $addr 物理地址
 */
public static function get_geo($addr){
    $ret=H::get_contents('http://api.map.baidu.com/geocoder/v2/?address='.$addr.'&output=json&ak=9f2feaa6d4a8a3eaae63d3b6d212fd13&callback=');
    $json= json_decode($ret,1);
    $arr=array('status'=>'1');
    if($json['status']=='0'){
        $arr['status']=$json['status'];
        if(isset($json['result']['location'])){
            $arr['longitude']=$json['result']['location']['lng'];
            $arr['latitude']=$json['result']['location']['lat'];
        }else{
            $arr['status']='2';
        }
    }
    return $arr;
}

/**
 * 根据百度坐标返回真实地址
 * @param $lat 纬度
 * @param $lng 经度
 */
public static function get_address($lat,$lng){
    $ret=H::get_contents("http://api.map.baidu.com/geocoder/v2/?location=$lat,$lng&output=json&ak=9f2feaa6d4a8a3eaae63d3b6d212fd13&callback=");
    $json= json_decode($ret,1);
    $addr='';
    if($json['status']=='0'){
        $addr=$json['result']['formatted_address'];
    }
    return $addr;
}

/**
 * 计算百度坐标之间距离
 * @param $lon2 起点经度
 * @param $lat2 起点纬度
 * @param $lon1 终点经度
 * @param $lat1 终点纬度
 * return 返回距离，单位：米
 */
public static function get_baidu_gps_dis($lon1, $lat1, $lon2, $lat2){
        $def_pi180= 0.01745329252; // PI/180.0
        $def_r =6370693.5; // radius of earth
        // 角度转换为弧度
        $ew1 = $lon1 * $def_pi180;
        $ns1 = $lat1 * $def_pi180;
        $ew2 = $lon2 * $def_pi180;
        $ns2 = $lat2 * $def_pi180;
        // 求大圆劣弧与球心所夹的角(弧度)
        $distance = sin($ns1) * sin($ns2) + cos($ns1) * cos($ns2) * cos($ew1 - $ew2);
        // 调整到[-1..1]范围内，避免溢出
        if ($distance > 1.0)
            $distance = 1.0;
        else if ($distance < -1.0)
            $distance = -1.0;
        // 求大圆劣弧长度
        $distance = $def_r * acos($distance);
        return $distance;
}
/**
 * 根据坐标和具体，返回附近的坐标经纬度的最大值和最小值
 * @param lat 纬度
 * @lon 经度
 * @raidus 单位米
 * return minLat,minLng,maxLat,maxLng
 */
public static function get_baidu_around($lon,$lat,$raidus){
    $PI = 3.14159265;

    $latitude = $lat;
    $longitude = $lon;

    $degree = (24901*1609)/360.0;
    $raidusMile = $raidus;

    $dpmLat = 1/$degree;
    $radiusLat = $dpmLat*$raidusMile;
    $minLat = $latitude - $radiusLat;
    $maxLat = $latitude + $radiusLat;

    $mpdLng = $degree*cos($latitude * ($PI/180));
    $dpmLng = 1 / $mpdLng;
    $radiusLng = $dpmLng*$raidusMile;
    $minLng = $longitude - $radiusLng;
    $maxLng = $longitude + $radiusLng;
    return array('ymin'=>$minLat,'ymax'=>$maxLat,'xmin'=>$minLng,'xmax'=>$maxLng);
}
/**
 * GPS坐标转换百度坐标
 * @param $x 经度
 * @param $y 纬度
 */
public static function gps_to_baidu($x,$y){
    //GPS坐标
    if($x>0 && $y>0){
        $xy=H::get_contents("http://api.map.baidu.com/ag/coord/convert?from=0&to=4&x=$x&y=$y");
        $xy=  json_decode($xy,1);
        unset($xy['error']);
        $xy['x']=  base64_decode($xy['x']);
        $xy['y']=  base64_decode($xy['y']);

    }else{
        $xy['x']=$x;
        $xy['y']=$y;
    }
    return $xy;
}

//==========================目录文件操作==========================

/**
 * 如果不存在，则根据传入目录自动创建多级目录
 *
 * @param  $dir 目录
 */
public static function mkdirs($dir,$mode=0777,$recursive=true) {
    if (!is_dir($dir)) {
        if (!H :: mkdirs(dirname($dir),$mode,$recursive)) {
            return false;
        }
        if (!mkdir($dir, $mode,$recursive)) {
            return false;
        }else{
            chmod($dir,$mode); //mkdir()函数指定的目录权限只能小于等于系统umask设定的默认权限。
        }
    }
    return true;
}
/**
 * @param $src 文件所在原目录
 * @param $dst 需要拷贝到的目标目录
 */
public static function file_copy($src,$dst) {  // 原目录，复制到的目录

    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                H::file_copy($src . '/' . $file,$dst . '/' . $file,$file);
            }
            else {
                @copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}
/**
 * 根据传入的目录名计算目录下的文件大小
 *
 * @param  $dirname 要统计的目录
 */
public static function dirsize($dirname) {
    $dirsize = 0;
    if ($dir_handle = opendir($dirname)) {
        while ($filename = readdir($dir_handle)) {
            $subFile = $dirname . DIRECTORY_SEPARATOR . $filename;
            if ($filename == '.' || $filename == '..') {
                continue;
            } else if (is_dir($subFile)) {
                $dirsize += H::dirsize($subFile);
            } else if (is_file($subFile)) {
                $dirsize += filesize($subFile);
            }
        }
        closedir($dir_handle);
    }
    return $dirsize;
}
/**
 * 获取某个文件夹下面的所有文件
 *
 * @param  $dir 某个文件夹所在的路径
 * @return array
 */
public static function get_files($dir) {
    $files = array();
    if (!file_exists($dir)) return $files;
    $key = 0;
    if (!file_exists($dir)) return $files;
    if ($handle = opendir($dir)) {
        while (($file = readdir($handle)) !== false) {
            if ($file != ".." && $file != ".") {
                if (is_dir($dir . "/" . $file)) {
                    // if($file=="css" ) continue;
                    //$files[$file] = H::get_files($dir . "/" . $file);
                } else {
                    $files[$key]['name'] = $file;
                    $files[$key]['size'] = filesize($dir . "/" . $file);
                    $files[$key]['update_time'] = filemtime($dir . "/" . $file);
					$key++;
                }
            }
        }
        closedir($handle);
        return $files;
    }
}

/**
 * 获取某个文件夹下面的所有文件
 *
 * @param  $dir 某个文件夹所在的路径
 * @return array
 */
public static function get_dirs($dir) {
    $dirArray=array();
    if (false != ($handle = opendir ( $dir ))) {
        $i=0;
        while ( false !== ($file = readdir ( $handle )) ) {
            if ($file != "." && $file != ".." && is_dir($dir.'/'.$file)) {
                $dirArray[$i]=$file;
                $i++;
            }
        }
        //关闭句柄
        closedir ( $handle );
    }
    return $dirArray;
}

/**
 * 删除目录及目录下的所以文件 清除缓存时可用到
 *
 * @param  $file 要删除的文件（含路径）
 * @return boolean 成功返回true,失败返回false;
 */
public static function del_dir($file) {
    if (!file_exists($file) && !is_dir($file)) return true; // 文件或目录不存在不需清除
    if (is_dir($file) && !is_link($file)) {
        foreach(glob($file . '/*') as $sf) {
            if (!H::del_dir($sf)) {
                return false;
            }
        }
        // 删除目录
        return @rmdir($file);
    } else {
        // 删除文件
        return @unlink($file);
    }
}

/**
 * 转换字节单位
 *
 * @param  $num 转换数字
 */
public static function num_bitunit($num) {
    $bitunit = array(' B', ' KB', ' MB', ' GB');
    for($key = 0;$key < count($bitunit);$key++) {
        if ($num >= pow(2, 10 * $key)-1) { // 1023B 会显示为 1KB
            $num_bitunit_str = (ceil($num / pow(2, 10 * $key) * 100) / 100) . " $bitunit[$key]";
        }
    }
    return $num_bitunit_str;
}

/**
 * 是否为AJAX提交
 * @return boolean
 */
public static function ajax_request() {
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
		return true;
	return false;
}

}
