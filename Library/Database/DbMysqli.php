<?php

namespace WuTi\Library\Database;


final class DbMysqli
{

	/**
	 * 数据库配置信息
	 */
	private $config = NULL;

	/**
	 * 数据库连接资源句柄
	 * @var \mysqli
	 */
	public $link = NULL;

	/**
	 * 最近一次查询资源句柄
	 */
	public $lastqueryid = NULL;

	/**
	 * DbMysqli constructor.
	 * @param $config [hostname,username,password,
	 * database,port,charset]
	 */
	public function __construct($config)
	{
		$this->config = $config;

	}


	/**
	 * 真正开启数据库连接
	 *
	 * @return bool|mixed
	 */
	public function connect()
	{
		$this->link = new \mysqli($this->config['hostname'], $this->config['username'], $this->config['password'],
			$this->config['database'], $this->config['port'] ? intval($this->config['port']) : 3306);
		if (mysqli_connect_error()) {
			$this->halt('Can not connect to MySQL server');
			return FALSE;
		}

		$charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
		if ($charset == 'utf-8') {
			$charset = 'utf8';
		}
		$this->link->set_charset($charset);
		return $this->link;
	}


	public function begin_transaction()
	{
		$this->link->begin_transaction();
	}


	public function commit()
	{
		$this->link->commit();
	}


	public function rollback()
	{
		$this->link->rollback();
	}


	/**
	 * 数据库查询执行方法
	 * @param $sql 要执行的sql语句
	 * @return 查询资源句柄
	 */
	private function execute($sql)
	{
		$this->_autoConnecting();
		$this->lastqueryid = $this->link->query($sql) or $this->halt($this->link->error, $sql);
		return $this->lastqueryid;
	}

	private function _autoConnecting(){
		if (!is_object($this->link)) {
			$this->connect();
		}else{
			try{
				if(!$this->link->ping()){
					$this->close();
					throw new \Exception('mysql connection has break');
				}
			}catch (\Exception $ex){
				$this->connect();
			}
		}
		return true;
	}


	private function parserWhere($where)
	{
		if (is_array($where)) {
			$whereTmp = '';
			foreach ($where as $k => $v) {
				if (!$whereTmp) {
					$whereTmp .= " WHERE `{$k}`='{$v}'";
				} else {
					$whereTmp .= " and `{$k}`='{$v}'";
				}
			}
			$where = $whereTmp;
		} else {
			$where = $where == '' ? '' : ' WHERE ' . $where;
		}
		return $where;
	}


	/**
	 * 执行sql查询
	 * @param $data        需要查询的字段值[例`name`,`gender`,`birthday`]
	 * @param $table        数据表
	 * @param $where        查询条件[例`name`='$name']
	 * @param $limit        返回结果范围[例：10或10,10 默认为空]
	 * @param $order        排序方式    [默认按数据库默认方式排序]
	 * @param $group        分组方式    [默认为空]
	 * @param $key            返回数组按键名排序
	 * @return array        查询结果集数组
	 */
	public function select($data, $table, $where = '', $limit = '', $order = '', $group = '', $key = '')
	{

		$where = $this->parserWhere($where);
		$order = $order == '' ? '' : ' ORDER BY ' . $order;
		$group = $group == '' ? '' : ' GROUP BY ' . $group;
		$limit = $limit == '' ? '' : ' LIMIT ' . $limit;
		$field = explode(',', $data);
		array_walk($field, [$this, 'add_special_char']);
		$data = implode(',', $field);

		$sql = 'SELECT ' . $data . ' FROM ' . $table . ' ' . $where . $group . $order . $limit;
		$this->execute($sql);
		if (!is_object($this->lastqueryid)) {
			return $this->lastqueryid;
		}

		$datalist = [];
		while (($rs = $this->fetch_next()) != FALSE) {
			if ($key) {
				$datalist[$rs[$key]] = $rs;
			} else {
				$datalist[] = $rs;
			}
		}
		$this->free_result();
		return $datalist;
	}


	/**
	 * 获取单条记录查询
	 * @param $data array       需要查询的字段值[例`name`,`gender`,`birthday`]
	 * @param $table string       数据表
	 * @param $where  array      查询条件
	 * @param $order        排序方式    [默认按数据库默认方式排序]
	 * @param $group        分组方式    [默认为空]
	 * @return array/null    数据查询结果集,如果不存在，则返回空
	 */
	public function get_one($data, $table, $where = '', $order = '', $group = '')
	{
		$where = $this->parserWhere($where);
		$order = $order == '' ? '' : ' ORDER BY ' . $order;
		$group = $group == '' ? '' : ' GROUP BY ' . $group;
		$limit = ' LIMIT 1';
		$field = explode(',', $data);
		array_walk($field, [$this, 'add_special_char']);
		$data = implode(',', $field);

		$sql = 'SELECT ' . $data . ' FROM  ' . $table . ' ' . $where . $group . $order . $limit;
		$this->execute($sql);
		$res = $this->fetch_next();
		$this->free_result();
		return $res;
	}


	/**
	 * 遍历查询结果集
	 * @param $type        返回结果集类型
	 *                    MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH
	 * @return array
	 */
	public function fetch_next($type = MYSQLI_ASSOC)
	{
		$res = $this->lastqueryid->fetch_array($type);
		if (!$res) {
			$this->free_result();
		}
		return $res;
	}


	/**
	 * 释放查询资源
	 * @return void
	 */
	public function free_result()
	{
		if (is_resource($this->lastqueryid)) {
			$this->lastqueryid->free();
			$this->lastqueryid = NULL;
		}
	}


	/**
	 * 直接执行sql查询
	 * @param $sql                            查询sql语句
	 * @return    boolean/query resource        如果为查询语句，返回资源句柄，否则返回true/false
	 */
	public function query($sql)
	{
		return $this->execute($sql);
	}


	/**
	 * 执行添加记录操作
	 * @param array $data        要增加的数据，参数为数组。数组key为字段值，数组值为数据取值
	 * @param string $table        数据表
	 * @return boolean
	 */
	public function insert($data, $table, $return_insert_id = FALSE, $replace = FALSE)
	{
		if (!is_array($data) || $table == '' || count($data) == 0) {
			return FALSE;
		}

		$fielddata = array_keys($data);
		$valuedata = array_values($data);
		array_walk($fielddata, [$this, 'add_special_char']);
		array_walk($valuedata, [$this, 'escape_string']);

		$field = implode(',', $fielddata);
		$value = implode(',', $valuedata);

		$cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
		$sql = $cmd . ' ' . $table . '(' . $field . ') VALUES (' . $value . ')';
		$return = $this->execute($sql);
		return $return_insert_id ? $this->insert_id() : $return;
	}


	/**
	 * 插入，当主键存在时则替换需更新的字段 ON DUPLICATE KEY UPDATE
	 * @param $data
	 * @param $table
	 * @param $primary
	 * @return bool|查询资源句柄
	 */
	public function insertByPrimary($data, $table, $primary, $return_insert_id = FALSE)
	{
		if (empty($data)) {
			return FALSE;
		}
		$set = '';
		$update = '';
		foreach ($data as $k => $v) {
			if ($set) {
				$set .= ', ' . $k . " = '" . $v . "'";
			} else {
				$set .= $k . " = '" . $v . "'";
			}
			if (!in_array($k, $primary)) {
				//非关键字字段
				if ($update) {
					$update .= ',';
				}
				$update .= $k . "=values(" . $k . ")";
			}
		}
		$sql = 'INSERT INTO ' . $table;
		$sql .= " set " . $set . " ON DUPLICATE KEY UPDATE " . $update;
		$return = $this->execute($sql);
		return $return_insert_id ? $this->insert_id() : $return;
	}


	/**
	 * 获取最后一次添加记录的主键号
	 * @return int
	 */
	public function insert_id()
	{
		$this->_autoConnecting();
		return $this->link->insert_id;
	}


	/**
	 * 执行更新记录操作
	 * @param $data        要更新的数据内容，参数可以为数组也可以为字符串，建议数组。
	 *                        为数组时数组key为字段值，数组值为数据取值
	 *                        为字符串时[例：`name`='phpcms',`hits`=`hits`+1]。
	 *                        为数组时[例: array('name'=>'phpcms','password'=>'123456')]
	 *                        数组可使用array('name'=>'+=1', 'base'=>'-=1');程序会自动解析为`name` = `name` + 1, `base` = `base` - 1
	 * @param $table        数据表
	 * @param $where        更新数据时的条件
	 * @return boolean
	 */
	public function update($data, $table, $where = '')
	{
		if ($table == '' or $where == '') {
			return FALSE;
		}

		$where = $this->parserWhere($where);
		//$where = ' WHERE ' . $where;
		$field = '';
		if (is_string($data) && $data != '') {
			$field = $data;
		} elseif (is_array($data) && count($data) > 0) {
			$fields = [];
			foreach ($data as $k => $v) {
				switch (substr($v, 0, 2)) {
					case '+=':
						$v = substr($v, 2);
						if (is_numeric($v)) {
							$fields[] = $this->add_special_char($k) . '=' . $this->add_special_char($k) . '+' . $this->escape_string($v,
									'', FALSE);
						} else {
							continue;
						}

						break;
					case '-=':
						$v = substr($v, 2);
						if (is_numeric($v)) {
							$fields[] = $this->add_special_char($k) . '=' . $this->add_special_char($k) . '-' . $this->escape_string($v,
									'', FALSE);
						} else {
							continue;
						}
						break;
					default:
						$fields[] = $this->add_special_char($k) . '=' . $this->escape_string($v);
				}
			}
			$field = implode(',', $fields);
		} else {
			return FALSE;
		}

		$sql = 'UPDATE ' . $table . ' SET ' . $field . $where;
		$ret= $this->execute($sql);
		if($ret){
			return $this->affected_rows();
		}
		return false;
	}


	/**
	 * 执行删除记录操作
	 * @param $table        数据表
	 * @param $where        删除数据条件,不充许为空。
	 *                        如果要清空表，使用empty方法
	 * @return boolean
	 */
	public function delete($table, $where)
	{
		$where = $this->parserWhere($where);
		if ($table == '' || $where == '') {
			return FALSE;
		}

		$sql = 'DELETE FROM ' . $table . ' ' . $where;
		$ret= $this->execute($sql);

		if($ret){
			return $this->affected_rows();
		}
		return false;
	}


	/**
	 * 获取最后数据库操作影响到的条数
	 * @return int
	 */
	public function affected_rows()
	{
		$this->_autoConnecting();
		return $this->link->affected_rows;
	}


	/**
	 * 获取数据表主键
	 * @param $table        数据表
	 * @return array
	 */
	public function get_primary($table)
	{
		$this->execute("SHOW COLUMNS FROM $table");
		while ($r = $this->fetch_next()) {
			if ($r['Key'] == 'PRI') {
				break;
			}
		}
		return $r['Field'];
	}


	/**
	 * 获取表字段
	 * @param $table        数据表
	 * @return array
	 */
	public function get_fields($table)
	{
		$fields = [];
		$this->execute("SHOW COLUMNS FROM $table");
		while ($r = $this->fetch_next()) {
			$fields[$r['Field']] = $r['Type'];
		}
		return $fields;
	}


	/**
	 * 检查不存在的字段
	 * @param $table 表名
	 * @return array
	 */
	public function check_fields($table, $array)
	{
		$fields = $this->get_fields($table);
		$nofields = [];
		foreach ($array as $v) {
			if (!array_key_exists($v, $fields)) {
				$nofields[] = $v;
			}
		}
		return $nofields;
	}


	/**
	 * 检查表是否存在
	 * @param $table 表名
	 * @return boolean
	 */
	public function table_exists($table)
	{
		$tables = $this->list_tables();
		return in_array($table, $tables) ? 1 : 0;
	}


	public function list_tables()
	{
		$tables = [];
		$this->execute("SHOW TABLES");
		while ($r = $this->fetch_next()) {
			$tables[] = $r['Tables_in_' . $this->config['database']];
		}
		return $tables;
	}


	/**
	 * 检查字段是否存在
	 * @param $table 表名
	 * @return boolean
	 */
	public function field_exists($table, $field)
	{
		$fields = $this->get_fields($table);
		return array_key_exists($field, $fields);
	}


	public function num_rows($sql)
	{
		$this->lastqueryid = $this->execute($sql);
		return $this->lastqueryid ? $this->lastqueryid->num_rows : 0;
	}


	public function num_fields($sql)
	{
		$this->lastqueryid = $this->execute($sql);
		return $this->lastqueryid ? $this->lastqueryid->field_count : NULL;
	}


	public function result($sql, $row)
	{
		$this->lastqueryid = $this->execute($sql);
		$this->lastqueryid->data_seek($row);
		$assocs = $this->lastqueryid->fetch_row();
		return $assocs[0];
	}


	public function error()
	{
		if (!is_object($this->link)) {
			return 0;
		}
		return $this->link->error;
	}


	public function errno()
	{
		if (!is_object($this->link)) {
			return 0;
		}
		return intval($this->link->errno);
	}


	public function version()
	{
		if (!is_object($this->link)) {
			return 0;
		}
		return $this->link->server_info;//server_version
	}

	/**
	 * 检测ping,确定当前是不是连接状态，当不连接时，会进行连接
	 * @return bool|mixed
	 */
	public function ping()
	{
		if ($this->link) {
			if (!$this->link->ping()) {
				$this->close();
				return $this->connect();
			}
			return TRUE;
		}
		return $this->connect();
	}


	public function close()
	{
		if ($this->link) {
			$this->link->close();
		}
		$this->link = NULL;
	}


	public function escape($str)
	{
		$this->_autoConnecting();
		return $this->link->real_escape_string($str);
	}


	public function halt($message = '', $sql = '')
	{
		$msg = "MySQL Query :｛$sql}, MySQL Error : " . $this->error() . ",MySQL Errno : " . $this->errno() . ", Message : {$message}";
		if (isset($this->config['debug']) && $this->config['debug']) {
			echo '<div style="font-size:12px;text-align:left; border:1px solid #9cc9e0; padding:1px 4px;color:#000000;font-family:Arial, Helvetica,sans-serif;"><span>' . $msg . '</span></div>';
			exit;
		} else {
			$error['cfg'] = [$this->config['hostname'], $this->config['port']];
			$error['msg'] = $msg;
			$this->close();
			\library\warWolf\log\logger::log($msg, 'mysql_exception');
			throw new \Exception($msg);
			return FALSE;
		}
	}


	/**
	 * 对字段两边加反引号，以保证数据库安全
	 * @param $value 数组值
	 */
	public function add_special_char(&$value)
	{
		if ('*' == $value || FALSE !== strpos($value, '(') || FALSE !== strpos($value, '.') || FALSE !== strpos($value,
				'`')) {
			//不处理包含* 或者 使用了sql方法。
		} else {
			$value = '`' . trim($value) . '`';
		}
		if (preg_match("/\b(select|insert|update|delete)\b/i", $value)) {
			$value = preg_replace("/\b(select|insert|update|delete)\b/i", '', $value);
		}
		return $value;
	}


	/**
	 * 对字段值两边加引号，以保证数据库安全
	 * @param $value 数组值
	 * @param $key 数组key
	 * @param $quotation
	 */
	public function escape_string(&$value, $key = '', $quotation = 1)
	{
		if ($quotation) {
			$q = '\'';
		} else {
			$q = '';
		}
		$value = $q . $value . $q;
		return $value;
	}
}


?>
