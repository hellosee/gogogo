<?php
class database  {
    public $query_count = 0; //数据库操作次数
    public $count_cache = 0; //查询COUNT计算总记录数缓存时间，单位：秒，0为不缓存
    private $conn; //数据库连接对象
    private static $dbase=null; //静态实例对象
    /**
     * 初始化类，根据传入的数据库参数连接数据库
     */
    final private function __construct($db_config=null) {
        $this->connect($db_config);
    }
    
    /**
     * 连接数据库
     */
    public function connect($db_config){
        if($db_config==null){
            $this -> conn = mysql_connect(DB_HOST , DB_USERNAME , DB_PASS) or die("do not connect database");
            $this->select_db(DB_DBNAME);
            mysql_query("set names " . DB_CHARSET);
        }else{
            $this -> conn = mysql_connect($db_config['host'], $db_config['user'], $db_config['pass']) or die("do not connect database");
            $this->select_db($db_config['dbname']);
            mysql_query("set names " . $db_config['charset']);
        }
    }
    
    //单例方法，用户访问实例的公共的静态方法
    public static function init($db_config=null){
        if(!self::$dbase instanceof self) {
            self::$dbase = new self($db_config);
        }
        return self::$dbase;
    }
    //创建__clone方法防止对象被复制克隆
    public function __clone(){
        trigger_error('Clone is not allow!',E_USER_ERROR);
    }
	/**
	 * 选择数据库
	 */
	public function select_db($dbname) {
		mysql_select_db($dbname, $this -> conn) or die("do not open database");
	}
    /**
     * 改变查询编码
     *
     * @param  $charset 编码 utf8,gbk 等
     */
    public function change_charset($charset) {
        mysql_query("set names " . $charset);
    }
    /**
     * 切换数据库
     *
     * @param  $db_config =array();
     * @param  host,user,pass,charset,dbname
     */
    public function change_db($db_config) {
        $this -> conn = mysql_connect($db_config['host'], $db_config['user'], $db_config['pass']) or die("do not connect database");
        //mysql_select_db($db_config['dbname'], $this -> conn) or die("do not open database");
		$this->select_db($db_config['dbname']);
        mysql_query("set names " . $db_config['charset']);
    }

    /**
     * 销毁类
     */
    public function __destruct() {
        if($this -> conn) {
            mysql_close($this -> conn);
        }
        unset($this->conn);
    }

    public function ping($db_config=null){
        if(!mysql_ping($this -> conn)){
            mysql_close($this -> conn); //注意：一定要先执行数据库关闭，这是关键
            $this ->connect($db_config);
        }
    }
     /**
     * 执行查询语句返回结果集
     *
     * @param  $sql 查询语句，不包括 order by limit等后缀
     * @param  $suffix 为where条件完毕之后的sql语句如order by 和 limit 等
     * @param  $is_total 用于获取分页数据的总记录数，默认值0为不count，不分页千万不要传递此参数
     * @param  $count_index 单表查询时，可以COUNT单独使用索引，提高速度
     */
    public function query($sql, $suffix = '', $is_total = 0,$count_index='') {
        //$this->ping();
        // 判断是否取记录总数,0为不取，1为取
        $_start = H :: getmicrotime();
        $total = 0;
        $sql_count_time = 0;
        $count_sql='';
        if ($is_total > 0) {

            if(strstr($sql,'where')){
                $count_sql = preg_replace("~select (.*?) from (.*?) where (.*?)~", "select count(*) as t from $2 $count_index where $3", strtolower($sql),1);
            }else{
                $count_sql = preg_replace("~select (.*?) from (.*?)~", "select count(*) as t from $2 $count_index ", strtolower($sql),1);
            }
            //如果没指定COUNT统计的索引，则取消统计时表名所带的强制索引
            if($count_index=='') $count_sql=preg_replace('~ use index\(.*?\) ~','',$count_sql);

            $countid = mysql_query($count_sql);//echo($count_sql);
            if($countid) {
                $total_rs = mysql_fetch_assoc($countid);//print_r($total_rs);
                mysql_free_result($countid); //释放结果内存
                unset($countid);
                $total = $total_rs['t'];
            }

            $this -> query_count++;
            $sql_count_time = H :: getmicrotime() - $_start;
        }
        //echo($sql . ' ' . $suffix.'<br>');
        // 查询取得记录列表
        $rs = mysql_query($sql . ' ' . $suffix);
        $this -> query_count++;
        $i = 0;
        $list = array();
        if ($rs) {
            while ($rows = mysql_fetch_assoc($rs)) {
                $list[$i] = $rows;
                $i++;
            }
            mysql_free_result($rs); //释放结果内存
            unset($rs);
        }
        // 返回该查询的记录总数和记录列表
        $querys = array('sql' => $sql . ' ' . $suffix, // SQL
            'error' => mysql_error(), // SQL报错信息
            'sql_time' => H :: getmicrotime() - $_start, // 整个SQL完成耗费时间
            'sql_time_count' => $sql_count_time, // 统计行数耗费时间
            'total' => $total, // 记录总数，如果$is_total=0，则该值为0
            'list' => $list,
            ); //print_r($querys);
        if(defined('SQL_ERR_LOG') && SQL_ERR_LOG=='1' && $querys['error']!='') {
            H::logs('sql_error',isset($_SERVER['REQUEST_URI'])?$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']:'');
            H::logs('sql_error',$querys['error'].$querys['sql']);
        }
        if(defined('SQL_LOG') && SQL_LOG=='1' && ($querys['sql_time']>=50 || $querys['sql_time_count']>=50)) {
            H::logs('sql',isset($_SERVER['REQUEST_URI'])?$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']:'');
            H::logs('sql','=='.$querys['sql_time'].' ms (count:'.$querys['sql_time_count'].' ms)-> '.$querys['sql'].'->'.$count_sql.chr(10));
        }
        return $querys;
    }
    /**
     * 执行插入操作
     *           特别返回：返回最后插入的自增ID值
     */
    public function query_insert($sql) {
        $_start = H :: getmicrotime();
        //$this->ping();
        mysql_query($sql);
        $this -> query_count++;
        $querys = array('sql' => $sql,
            'error' => mysql_error(),
            'sql_time' => H :: getmicrotime() - $_start, // 整个SQL完成耗费时间
            'autoid' => mysql_insert_id(),
            );
        if(defined('SQL_ERR_LOG') && SQL_ERR_LOG=='1' && $querys['error']!='') {
            H::logs('sql_error',isset($_SERVER['REQUEST_URI'])?$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']:'');
            H::logs('sql_error',$querys['error'].$querys['sql']);
        }
        if(defined('SQL_LOG') && SQL_LOG=='1' && $querys['sql_time']>=50) {
            H::logs('sql',isset($_SERVER['REQUEST_URI'])?$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']:'');
            H::logs('sql','=='.$querys['sql_time'].' ms ->'.$querys['sql']);
        }
            return $querys;
    }
    /**
     * 执行更新、删除操作
     */
    public function query_update($sql) {
        $_start = H :: getmicrotime();
        //$this->ping();
        mysql_query($sql);
        $this -> query_count++;
        $querys = array('sql' => $sql,
            'error' => mysql_error(),
            'rows' => mysql_affected_rows(), //返回受影响行数
            'sql_time' => H :: getmicrotime() - $_start, // 整个SQL完成耗费时间
            );
        if(defined('SQL_ERR_LOG') && SQL_ERR_LOG=='1' && $querys['error']!='') {
            H::logs('sql_error',isset($_SERVER['REQUEST_URI'])?$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']:'');
            H::logs('sql_error',$querys['error'].$querys['sql']);
        }
        if(defined('SQL_LOG') && SQL_LOG=='1' && $querys['sql_time']>=50) {
            H::logs('sql',isset($_SERVER['REQUEST_URI'])?$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']:'');
            H::logs('sql','=='.$querys['sql_time'].' ms ->'.$querys['sql']);
        }
        return $querys;
    }
    /**
     * 单表插入
     *
     * @param  $table_name 表名
     * @param  $fields 字段和表单域对应值，格式如array("fields1"=>val1,"fields2"=>val2....)，插入时候循环遍历
     * @param  $type 0=略过插入 1=替换插入
     */
    public function single_insert($table_name, $fields, $type = 0,$return_type=0) {
        if (!is_array($fields) || count($fields) == 0) return array('sql' => '', 'error' => '插入失败，插入字段为空', 'sql_time' => 0, 'autoid' => 0);

        $sql_field = "";
        $sql_value = "";
        // 遍历字段和值
        foreach($fields as $key => $value) {
            if(in_array($key,array('gps_point'))){
                $sql_field .= ",`$key`";
                $sql_value .= ",$value";
            }else{
                $sql_field .= ",`$key`";
                $sql_value .= ",'$value'";
            }
        }

        $sql_field = substr($sql_field, 1);
        $sql_value = substr($sql_value, 1);
        if ($type == 0) {
            $sql = "insert ignore  into $table_name ($sql_field) values ($sql_value)"; //组合SQL
        } else {
            $sql = "replace into $table_name ($sql_field) values ($sql_value)"; //组合SQL
        }
        //H::logs ( 'partner_update',$sql);
        
        $result = $this -> query_insert($sql);
        if($return_type){
            return $sql;
        }else{
            return $result;
        }
    }
    /**
     * 单表更新
     *
     * @param  $table_name 表名
     * @param  $fields 字段和表单域对应值，格式如array("fields1"=>val1,"fields2"=>val2....)，插入时候循环遍历
     * @param  $where 更新的条件语句
     */
    public function single_update($table_name, $fields, $where = '') {
        if (!is_array($fields) || count($fields) == 0) return array('sql' => '', 'error' => '更新失败，插入字段为空', 'sql_time' => 0);

        $sql_set = "";
        // 遍历字段和值
        foreach($fields as $key => $value) {
            if(in_array($key,array('gps_point'))){
                $sql_set .= ",`$key`=$value";
            }else{
                $sql_set .= ",`$key`='$value'";
            }
        }

        $sql_set = substr($sql_set, 1);
        if (strlen($where) > 0) $where = "where $where";
        $sql = "update $table_name set $sql_set $where"; //组合SQL
        $result = $this -> query_update($sql);

        return $result;
    }

    /**
     * 单表查询
     *
     * @param  $params
     * @param  $ -> $table_name     表名 必填
     * @param  $ -> $fields         字段 field1,field2,...
     * @param  $ -> $where          更新的条件语句
     * @param  $ -> $suffix         order by , limit 语句
     * @param  $ -> $count          计算总量 0=不计算 1=计算
     * @param  $ -> $pagesize       分页大小
     */

    public function single_query($params) {
        // 初始化
        $table_name = isset($params['table_name'])?$params['table_name']:'';
        $fields = isset($params['fields'])?$params['fields']:'*';
        $where = isset($params['where'])?$params['where']:'';
        $suffix = isset($params['suffix'])?$params['suffix']:'';
        $count = isset($params['count'])?$params['count']:0;
        $pagesize = isset($params['pagesize'])?$params['pagesize']:10;
        $count_index=isset($params['count_index'])?$params['count_index']:'';
        if (strlen($where) > 0) $where = "where $where";

        $sql = "select $fields from $table_name $where";
        //echo $sql;
        $result = $this -> query($sql, $suffix, $count,$count_index);
        $result['pagebar'] = H :: pagebar(array('total' => $result['total'], 'pagesize' => $pagesize, 'rewrite' => 2));
        return $result;
    }
    /**
     * 单表删除
     *
     * @param  $ -> $table_name     表名 必填
     * @param  $ -> $where         删除的限制条件，必填
     */
    public function single_del($table_name, $where) {
        // 判断限制
        if (empty($table_name) || empty($where)) return false;
        if (strlen($where) > 0) $where = "where $where";

        $sql = " delete from $table_name $where";
        $result = $this -> query_update($sql);
        return $result;
    }

    /**
     * 返回limit语句
     *
     * @param  $pagesize 分页大小
     * @param  $p 当前页码
     */
    public function get_limit_sql($pagesize = 10, $p = 1) {
        return "limit " . ($p-1) * $pagesize . ",$pagesize";
    }

    /**
     * 联表查询（2张表)
     *
     * @param  $params
     * @param  $ -> $table_name     表名 必填
     * @param  $ -> $fields         字段 field1,field2,...
     * @param  $ -> $where          更新的条件语句
     * @param  $ -> $suffix         order by , limit 语句
     * @param  $ -> $count          计算总量 0=不计算 1=计算
     * @param  $ -> $pagesize       分页大小
     * 示范 params=array('table1'=>'cate','table2'=>'info','joinon'=>'cate_id#last_cate_id','fields'=>'a.field,b.felde'......)
     */

    public function join_query($params) {
        // 初始化
        $table1 = isset($params['table1'])?$params['table1']:'';
        $table2 = isset($params['table2'])?$params['table2']:'';
        // 关联字段 cate_id#last_cate_id 以#分隔
        $joinon = isset($params['joinon'])?$params['joinon']:'';
        $joinon = explode("#", $joinon);
        $fields = isset($params['fields'])?$params['fields']:'*';
        $where = isset($params['where'])?$params['where']:'';
        $suffix = isset($params['suffix'])?$params['suffix']:'';
        $count = isset($params['count'])?$params['count']:0;
        $total = isset($params['total'])?$params['total']:0;
        $left_index = isset($params['left_index'])?$params['left_index']:'';
        $right_index = isset($params['right_index'])?$params['right_index']:'';
        $pagesize = isset($params['pagesize'])?$params['pagesize']:10;
        if (strlen($where) > 0) $where = "where $where";
        $sql = "select $fields from $table1 as a ".$left_index." left join $table2 as b ".$right_index." on a." . $joinon[0] . "=b." . $joinon[1] . " $where";
        $result = $this -> query($sql, $suffix, $count);
        $result['pagebar'] = H :: pagebar(array('total' => ($total>0?$total:$result['total']), 'pagesize' => $pagesize, 'rewrite' => 2));
        return $result;
    }
    /**
     * 返回单行记录
     *
     * @param  $sql SQL查询
     * @param  $suffix 后缀
     */
    public function scalar($sql) {
        $rs = $this -> query($sql);
        if (count($rs['list']) == 1) {
            return $rs['list'][0];
        } else {
            return array();
        }
    }
    /**
	 *检查表是否存在
	 * $params
	 * ->$table 表名 必填
     */
	public function check_exists_table($table) {
		$res = $this -> query("SHOW TABLES");
		$havbz = false;
		foreach ($res['list'] as $tablename) {
			$val = array_values($tablename);
			if ($val[0] == TB_PRE.$table) {
				$havbz = true;
				break;
			}
		}
		return $havbz;
	}
    /**
     * 查询某张表的某条数据
     * $table 表名
     * $fields 字段
     * $where 条件
     */
    public function find($table,$fields,$where) {
        $fields = trim($fields);
        $sql = "select ".$fields." from ".$table." where ".$where." limit 1";
        //echo $sql;
        $tmp = $this->query($sql);//print_r($tmp);

        if (count($tmp['list']) == 1) {
            if(count($tmp['list'][0]) == 1) return $tmp['list'][0][$fields]; //单个字段直接返回数据
            return $tmp['list'][0];
        }
        return false;
    }
    /**
     * 根据条件对某张表的某个字段进行求和
     * $table 表名
     * $fields 字段
     * $where 条件
     */
    public function sum($table,$fields,$where='') {
        $sql = "select sum(".$fields.") as s from ".$table.($where ? " where ".$where : '');
        $tmp = $this->scalar($sql);
        if(!isset($tmp['s']) || empty($tmp['s'])) $tmp['s'] = 0;
        return $tmp['s'];
    }
    /**
     * 根据条件对某张表的某个字段进行统计
     * $table 表名
     * $fields 字段
     * $where 条件
     */
    public function counts($table,$fields,$where='') {
        $sql = "select count(".$fields.") as t from ".$table.($where ? " where ".$where : '');
        //echo $sql;
        $tmp = $this->scalar($sql);
        if(!isset($tmp['t']) || empty($tmp['t'])) $tmp['t'] = 0;
        return $tmp['t'];
    }

	/**
	 * 批量保存数据，保证结构的完整性
	 * @param string $table 操作的数据表
	 * @param array $params 需要保存的数据 array(array('name'=>'a1'),array('name'=>'a2'));
	 */
    public function addAll($table,$params){   	
    	//获取字段   	
    	$fields = $key_value = $value = array();    	    	
    	
    	//1  获取数据表的结构 
    	/*$query = $this->query("DESC $table");
    	
    	//整理表字段。
    	foreach ($query['list'] as $k=>$val){
    		$fields[] = $val['Field'];
    	}*/
    	
    	//2 整理表字段。 采用第二种方案
    	foreach ($params as $k=>$val){
    		foreach ($val as $field=>$vals){
    			$fields[] = $field;
    		}
    		break;
    		//整理字段完毕
    	}
    	
    	//整理字段对应的值。
    	foreach ($params as $k=>$data){
    		$key_value = array();
    		
    		foreach ($fields as $field){
    			//同时过滤重复字段
    			$key_value[$field] = $data[$field];
    		}
    		$value[] = "('".implode("','", $key_value)."')";
    	}
    	//字段合并
    	$field = "(`".implode('`,`', $fields)."`)";
    	$value = implode(",", $value);
    	//mysql insert有插入多条语法，拼接sql语句，table_name表名
    	$sql = "insert into $table $field values ".$value;
    	//echo $sql.'<br>';
    	//echo date ( 'Y-m-d H:i:s' );
    	//执行，插入$query_num条数据
    	$query = mysql_query($sql);    	
    	
    	return $query;
    }

}

?>