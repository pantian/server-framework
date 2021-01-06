<?php
/**
 * Created by PhpStorm.
 * User: pantian
 * Date: 2014/12/27
 * Time: 0:24
 */

namespace PTLibrary\DB;


use PTLibrary\Cache\YacCache;
use PTLibrary\Error\ErrorHandler;
use PTLibrary\Exception\ThrowException;
use PTLibrary\Log\Log;
use PTLibrary\Tool\Tool;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Simps\DB\DB;
use Simps\DB\PDO;
use Swoole\Coroutine;

class PtPDO {
	/**
	 * @var \PDO
	 */
	private $db = null;

	/**
	 * 表名
	 *
	 * @var string
	 */
	private $_table = '';
	/**
	 * 数据名
	 *
	 * @var string
	 */

	public $Fields = array();
	/**
	 * 数据库名
	 *
	 * @var string
	 */
	private $db_name = '';
	/**
	 * 表前缀
	 *
	 * @var string
	 */
	private $table_prefix = '';
	/**
	 * 最后执行的数据
	 *
	 * @var array
	 */
	private $_lastExecuteData = array();

	/**
	 * 搜索条件
	 *
	 * @var null
	 */
	private $_where = null;
	/**
	 * where条件的预处理数据数组
	 *
	 * @var array
	 */
	private $selectData = array();
	/**
	 * 表全名，包含数据库名
	 *
	 * @var string
	 */
	protected $fullTableName = '';

	private $LastSql = '';

	protected $pool;

	/**
	 * sql sleep 函数
	 *
	 * @var int
	 */
	private $sql_sleep = 0;


	const EXECUTE_TYPE_SELECT = 1;
	const EXECUTE_TYPE_UPDATE = 2;
	const EXECUTE_TYPE_INSERT = 3;

	/**
	 * 是否忽略表前缀
	 *
	 * @var bool
	 */
	public $_ignoreTablePrefix = false;

	protected $_table_as = '';
	/**
	 * 以此字段值做为查询结果数组索引值
	 * 如果不存在，则把主键值做为查询结果数组key
	 *
	 * @var string
	 */
	protected $_res_index_field = '';

	protected static $_is_hased_table = false;

	protected static $tables = [];

	/**
	 * 主键
	 *
	 * @var null
	 */
	public $PK = null;
	/**
	 * 是否随机
	 *
	 * @var bool
	 */
	protected $isRand = false;

	private $sql = array();
	/**
	 * 整个进程的sql记录
	 *
	 * @var array
	 */
	private static $allSqlHistory = [];


	/**
	 * @var \PDO
	 */
	protected $_pdo;

	private $in_transaction = false;


	private static $instance = null;

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return clone self::$instance;
	}

	public function __destruct() {
	}

	public function __construct() {
		$this->pool = PDO::getInstance();

	}

	public function __clone() {
		$this->init();
	}

	/**
	 * @return string
	 */
	public function getTableHash() {
		return md5( $this->db_name . $this->fullTableName );
	}

	/**
	 * @return bool
	 */
	public function isResIndexField(): bool {
		return $this->_res_index_field;
	}

	/**
	 * 设置返回数组的key值字段
	 *
	 * @param string $field
	 */
	public function setResIndexField( string $field ) {
		$this->_res_index_field = $field;
	}

	/**
	 * 初始化
	 */
	public function init() {

//		$this->getDB();
//		$this->clearCondition();
	}

	/**
	 * @return string
	 */
	public function getTableAs() {
		return $this->_table_as;
	}

	/**
	 * @param string $table_as
	 */
	public function setTableAs( $table_as ) {
		$this->_table_as = $table_as;
	}

	/**
	 * 添加sql记录
	 *
	 * @param $sql
	 */
	protected function addSqlHistory( $sql ) {
		$this->sql[] = $sql;
		array_push( self::$allSqlHistory, $sql );
		if ( count( self::$allSqlHistory ) > 200 ) {
			array_pop( self::$allSqlHistory );
		}
	}

	/**
	 * 获取所有sql历史记录
	 *
	 * @return array
	 */
	public function getSqlHistory() {
		return self::$allSqlHistory;
	}


	/**
	 * @return string
	 */
	public function getTablePrefix() {
		return $this->table_prefix;
	}

	/**
	 * @param string $table_prefix
	 */
	public function setTablePrefix( $table_prefix ) {
		$this->table_prefix = $table_prefix;
	}

	public function sleep( $time = 0 ) {
		$this->sql_sleep = (float) $time;
		if ( $this->sql_sleep > 0 ) {
			$sleepSql = "SELECT SLEEP(?)";
			$this->exeCuteSqlData( $sleepSql, [ $this->sql_sleep ] );
			$this->sql_sleep = 0.0;
		}

		return $this;
	}


	/**
	 * @return \PDO|\Swoole\Database\PDOProxy
	 */
	public function getDB() {
		$this->db = PDO::getInstance()->getConnection();

		return $this->db;
	}

	public function rand( $isRand = true ) {
		$this->isRand = $isRand;
	}


	/**
	 * @return string
	 */
	public function getDbName() {
		return $this->db_name;

	}

	/**
	 * @param string $db_name
	 */
	public function setDbName( $db_name ) {
		$this->db_name = $db_name;
		//$this->PDOConnect->currentDbName != $db_name && $this->selectDB( $db_name );
	}

	/**
	 * 标示表是否存在
	 *
	 * @var bool
	 */
	protected static $_table_exists = false;

	/**
	 * 查询数据库下的表名
	 *
	 * @return array|false
	 * @throws \Bin\Exception\DBException
	 */
	public function getAllTables() {
		if ( ! $this->db_name ) {
			ThrowException::DBException( ErrorHandler::DB_NAME_EMPTY );
		}
		$tables = Tool::getArrVal( $this->db_name, self::$tables );
		if ( $tables ) {
			return $tables;
		}

		$sql    = 'select * from INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA=:dbname';
		$data   = $this->exeCuteSqlData( $sql, [ 'dbname' => $this->db_name ] );
		$tables = [];
		foreach ( $data as $info ) {
			$tables[ $info['TABLE_NAME'] ] = $info;
		}
		$tables && self::$tables[ $this->db_name ] = $tables;

		return $tables;

	}

	/**
	 * 执行PDO查询
	 *
	 * @param       $sql
	 * @param array $data
	 * @param null  $type
	 *
	 * @return array
	 * @throws \PTLibrary\Exception\DBException
	 */
	function exeCuteSqlData( $sql, $data = [], $type = self::EXECUTE_TYPE_SELECT ) {
		$this->addSqlHistory( $sql );
		$this->realGetConn();
		$statement = $this->_pdo->prepare( $sql );
		$statement->setFetchMode( \PDO::FETCH_ASSOC );
		if ( $statement->execute( $data ) === false ) {
			ThrowException::DBException( ErrorHandler::DB_ERROR, '数据执行错误' );
		}
		switch ( $type ) {
			case self::EXECUTE_TYPE_SELECT:
				$res = $statement->fetchAll();
				break;
			case self::EXECUTE_TYPE_UPDATE:
				$res = $statement->rowCount();
				break;
			case self::EXECUTE_TYPE_INSERT:
				$res = $this->_pdo->lastInsertId();
				break;
		}
		$statement->closeCursor();
		$this->release( $this->_pdo );

		return $res;
	}

	/**
	 * 检测表是否存在
	 *
	 *
	 * @return bool
	 */
	public function table_exists() {
		if ( self::$_is_hased_table ) {
			return true;
		}
		$tables = self::getAllTables();

		if ( isset( $tables[ $this->_table ] ) ) {
			self::$_is_hased_table = true;

			return true;
		}

		return false;

	}

	/**
	 * 添加、删除表与字段
	 *
	 * @param $sql
	 *
	 * @return array
	 * @throws DBException
	 */
	public function create_table( $sql ) {

		$result = $this->getDB()->prepare( $sql );
		$result->execute();
		$res = $result->fetchAll();

		return $res;

	}


	/**
	 * @return bool|mixed
	 */
	public function gls() {
		if ( ! empty( $this->sql ) ) {
			return $this->sql[ count( $this->sql ) - 1 ];
		} else {
			return false;
		}
	}

	/**
	 * 获取最后执行的sql语句
	 *
	 * @return string
	 */
	public function getLastSql() {
		return $this->LastSql;
	}

	/**
	 * 获取所有的sql记录
	 *
	 * @return array
	 */
	public function getAllSql() {
		return $this->sql;
	}

	/**
	 *获取字段
	 *
	 * @return string
	 * @throws \PTLibrary\DB\DBException
	 */
	public function getFields() {
		try {
			$this->Fields = YacCache::getYacInstance()->get( $this->getTableHash() );
			if ( $this->Fields ) {
				return $this->Fields;
			}
			if ( $this->getFullTableName() ) {
				$sql  = 'show full fields from ' . $this->fullTableName;
				$data = $this->exeCuteSqlData( $sql );
				if ( $data ) {
					foreach ( $data as $row ) {
						$this->Fields[ $row['Field'] ] = $row;
						if ( empty( $this->PK ) && $row['Key'] == 'PRI' ) {
							$this->PK = $row['Field'];
						}
					}

				}
				YacCache::getYacInstance()->set( $this->getTableHash(), $this->Fields );

				return $this->Fields;
			} else {
				Log::error( '表名为空' );
				throw new DBException( ErrorHandler::DB_TABLE_EMPTY );
			}

		} catch ( \Exception $e ) {
			Log::error( '数据库异常：code = ' . $e->getCode() . '； 错误信息 ' . $e->getMessage() );
			throw $e;
		}
	}


	/**
	 *过滤数据
	 */
	public function filterData( &$data ) {
		if ( ! is_array( $this->Fields ) ) {
			return false;
		}
		if ( ! is_array( $data ) ) {
			return false;
		}
		foreach ( $data as $key => $value ) {
			if ( ! array_key_exists( $key, $this->Fields ) ) {
				unset( $data[ $key ] );
			}
		}
	}

	/**
	 * 增加
	 *
	 * @param       $data
	 * @param false $filter_null
	 *
	 * @return false
	 * @throws \PTLibrary\Exception\DBException
	 */
	public function add( $data, $filter_null = false ) {

		try {
			if ( ! $data ) {
				Log::error( '添加的数据为空' );

				return false;
			}
			if ( $filter_null === true ) {
				foreach ( $data as $key => $value ) {
					if ( is_null( $value ) ) {
						unset( $data[ $key ] );
					}
				}
			}
			$this->getInsertInto( $data );
			$this->_lastExecuteData = $data;
			$res                    = $this->exeCuteSqlData( $this->InsertPrepare, $data, self::EXECUTE_TYPE_INSERT );

		} catch ( \Exception $e ) {

			ThrowException::DBException( ErrorHandler::DB_INSERT_FAIL, $e->getMessage() );

		}

	}

	/**
	 * 批量增加
	 *
	 * @param $data
	 *
	 * @return bool
	 * @throws DBException
	 */
	public function addAll( $data ) {
		try {
			if ( $data ) {
				foreach ( $data as $_data ) {
					$this->add( $_data );
				}
			}
		} catch ( \Exception $e ) {
			throw new DBException( ErrorHandler::DB_INSERT_FAIL );
		}

		return false;
	}

	private $InsertPrepare = '';
	private $UpdatePrepare = '';

	/*
	 *插入预处理
	 */
	public function getInsertInto( &$data ) {
		$this->setPrepareData( $data );
		//$this->prepare( $this->InsertPrepare );
	}


	/**
	 *PDO预处理数据
	 *
	 * @param $data
	 */
	public function setPrepareData( &$data ) {
		$this->filterData( $data );
		$tmp                 = null;
		$this->InsertPrepare = 'INSERT INTO ' . $this->getFullTableName() . ' (';
		$this->UpdatePrepare = 'UPDATE ' . $this->getFullTableName() . ' SET ';
		$value_key           = 'VALUES(';
		foreach ( $data as $key => $value ) {
			$_key                = ':' . $key;
			$this->InsertPrepare .= " `$key`,";
			if ( $value && is_array( $value ) ) {
				//数组处理
				$_a_key = $value[0];
				switch ( $_a_key ) {
					case 'expression':
						//表达式的值处理
						$value_key           .= "$value[1] ,";
						$this->UpdatePrepare .= " `$key` = $value[1] ,";
						break;
				}
			} else {
				$this->UpdatePrepare .= " `$key` = $_key ,";
				$value_key           .= "$_key ,";
				$tmp[ $_key ]        = $this->valueForm( $key, $value );
				$tmp[ $_key ] === null && $tmp[ $_key ] = null;
			}
		}
		$this->InsertPrepare = substr( $this->InsertPrepare, 0, - 1 ) . ') ' . substr( $value_key, 0, - 1 ) . ')';
		$this->UpdatePrepare = substr( $this->UpdatePrepare, 0, - 1 );
		$data                = $tmp;
	}

	/**
	 * 自增
	 *
	 * @param     $field
	 * @param int $step
	 *
	 * @return array
	 */
	public function setInc( $field, $step = 1 ) {
		return $this->changeFieldForInt( $field, $step, 1 );
	}

	/**
	 * 自减
	 *
	 * @param           $field
	 * @param int|float $step
	 *
	 * @return array
	 */
	public function setRnc( $field, $step = 1 ) {
		return $this->changeFieldForInt( $field, $step, 0 );
	}


	/**
	 * 对int 类型字段进行自变
	 *
	 * @param     $field
	 * @param int $step
	 * @param int $type 1自加，0自减
	 *
	 * @return array|bool
	 * @throws DBException
	 */
	public function changeFieldForInt( $field, $step = 1, $type = 1 ) {
		$fullTable = $this->getFullTableName();
		try {
			$typeStr  = $type == 1 ? '+' : '-';
			$fieldStr = '';
			if ( is_array( $field ) ) {
				foreach ( $field as $_field => $_step ) {
					if ( $this->fieldTypeIsInt( $_field ) && $_field != $this->PK && isset( $this->Fields[ $_field ] ) ) {
						$fieldStr .= ( $fieldStr ? ',' : '' ) . "{$fullTable}.{$_field}={$_field} {$typeStr} {$_step} ";
					}
				}

			} elseif ( is_string( $field ) ) {

				if ( $this->fieldTypeIsInt( $field ) && $field != $this->PK && isset( $this->Fields[ $field ] ) ) {
					$fieldStr = "{$fullTable}.{$field}={$field} {$typeStr} {$step} ";
				}
			}
			if ( $fieldStr ) {
				$where = $this->getWhere();
				if ( $where ) {

					$sql = "UPDATE {$fullTable} set $fieldStr {$where}";

					$sth  = $this->exeCuteSqlData( $sql, $this->selectData );
					$rows = $sth->rowCount();

					return $rows;
				}
			} else {
				Log::error( '字段类型不符合' );
			}

			return false;
		} catch ( \Exception $e ) {
			Log::error( 'sql:' . $this->getLastSql() );
			throw new DBException( ErrorHandler::DB_SAVE_FAIL, $e->getMessage() );

		}

	}

	/**
	 * 自动递增操作，单个字段递增，多个字段则用数组形式，主键不存在则会自动添加
	 *
	 * 条件只能是主键,或唯一索引字段为查询条件
	 *
	 * <pre>
	 * $field数组形式,$step则无效
	 * $field=array('number'=>1,'number2'=>2.2)
	 *
	 * </pre>
	 *
	 * @param string|array $field 字段
	 * @param int|float    $step  自增数值
	 *
	 * @return bool|int|mixed|string
	 * @throws \PTLibrary\Exception\DBException
	 */
	public function setAutoInc( $field, $step = 1 ) {


		if ( ! $field ) {
			return false;
		}
		$insertData = [];
		$updateData = [];
		$this->getWhere();
		//把where数据加到insert数组中

		if ( is_array( $field ) ) {
			foreach ( $field as $_field => $_step ) {
				$updateData[ $_field ] = [ 'expression', $_field . ' + ' . floatval( $_step ) ];
				$insertData[ $_field ] = floatval( $_step );
			}

		} else if ( is_string( $field ) ) {
			$updateData[ $field ] = [ 'expression', $field . ' + ' . $step ];
			$insertData[ $field ] = floatval( $step );
		}
		$this->setPrepareData( $updateData );//先生成更新的sql

		$updateSql = $this->UpdatePrepare;
		$updateSql = str_replace( 'UPDATE ' . $this->getFullTableName() . ' SET', 'UPDATE', $updateSql );
		foreach ( $this->selectData as $w_key => $w_value ) {
			$_data_key                = substr( $w_key, 3 );
			$insertData[ $_data_key ] = $w_value;
		}
		$this->setPrepareData( $insertData );//再生成insert sql
		$sql                    = "{$this->InsertPrepare} ON DUPLICATE KEY {$updateSql}";
		$this->_lastExecuteData = $insertData;

		$res = $this->exeCuteSqlData( $sql, $insertData, self::EXECUTE_TYPE_UPDATE );

		return $res;


	}

	/**
	 *查询
	 */
	public function select() {
		try {
			$filed     = $this->getSelectField();
			$fullTable = $this->getFullTableName();
			$sql       = '';
			$sql       .= "SELECT $filed FROM $fullTable ";
			$join      = $this->getJoin();
			$join && $sql .= $join;
			$where      = $this->getWhere();
			$selectData = $this->selectData;

			$this->selectData = array();
			$where && $sql .= "$where ";
			$group = $this->getGroup();
			$group && $sql .= $group;
			if ( $this->isRand ) {
				$order        = ' ORDER BY rand()';//随机记录
				$this->isRand = false;
			} else {
				$order = $this->getOrder();
			}
			$order && $sql .= $order;
			$limit = $this->getLimit();
			$limit && $sql .= $limit;

			$this->_lastExecuteData = $selectData;

			$res = $this->exeCuteSqlData( $sql, $selectData, self::EXECUTE_TYPE_SELECT );
			$this->clearCondition();
			if ( $this->_res_index_field ) {
				$new_res = [];
				foreach ( $res as $val ) {
					if ( ! isset( $val[ $this->_res_index_field ] ) ) {
						$this->_res_index_field = $this->PK;
					}
					$key = Tool::getArrVal( $this->_res_index_field, $val );
					if ( strlen( $key ) > 0 ) {
						$new_res[ $key ] = $val;
					} else {
						$new_res[] = $val;
					}
				}

				return $new_res;
			}

			return $res;
		} catch ( \Exception $e ) {
			Log::error(
				'查询异常：sql:' . $this->getLastSql() . '; ' . $e->getCode() . $e->getMessage() . ';' . print_r( $selectData, true )
			);
			ThrowException::DBException( ErrorHandler::DB_SELECT_FAIL );
		}

	}

	/**
	 * 清除查询条件
	 */
	public function clearCondition() {
		$this->_filed    = [];
		$this->_limit    = [];
		$this->_order    = [];
		$this->_group    = [];
		$this->_where    = [];
		$this->sql_sleep = 0;
	}

	public function find() {
		$this->limit( 1 );
		$rs = $this->select();
		if ( $rs ) {
			return $rs[0];
		}

		return false;
	}


	private $_order = array();

	private $_group = array();

	/**
	 *排序设置
	 *
	 * 参数：array('id'=>'desc') 或 array('id'=>1)
	 * desc=-1 倒序
	 * asc=1 正序
	 *
	 * @param array $field
	 *
	 * @return $this
	 */
	public function order( array $field ) {
		$this->_order = $field;

		return $this;
	}

	/**
	 * 分组设置
	 *
	 * 参数：array('id') 或 'id'
	 *
	 * @param array $field
	 *
	 * @return $this
	 */
	public function group( array $field ) {
		$this->_group = $field;

		return $this;
	}

	/**
	 * 获取排序
	 *
	 * @return bool|string
	 */
	public function getOrder() {

		if ( is_array( $this->_order ) && $this->_order ) {
			foreach ( $this->_order as $key => $val ) {
				$temp[] = " `$key` " . ( ( is_string( $val ) ) ? $val : ( ( $val === ( - 1 ) ) ? 'ASC' : 'DESC' ) );
			}
			$str = ' ORDER BY ' . implode( ',', $temp );

			return $str;
		}

		return false;
	}

	/**
	 * 获取分组
	 *
	 * @return bool|string
	 */
	public function getGroup() {

		if ( $this->_group && is_array( $this->_group ) ) {
			$str = ' group BY ' . implode( ',', $this->_group );

			return $str;
		}

		return false;
	}

	private $joinArr = [];

	/**
	 *join 设置
	 *
	 * @param      $table
	 * @param null $as
	 * @param null $on
	 * @param      $filed
	 *
	 * @return $this
	 */
	public function join( $table, $as = null, $on = null, $filed = null ) {
		$as && $as = 'as ' . $as;
		$on && $on = 'on ' . $on;
		$filed && $this->field( $filed );
		$this->joinArr[ $table ] = "join $table $as $on";

		return $this;
	}

	/**
	 *获取join字符串
	 *
	 * @return array|string
	 */
	public function getJoin() {
		if ( $this->joinArr ) {
			if ( is_array( $this->joinArr ) ) {
				return implode( ' ', $this->joinArr );
			} else {
				return $this->joinArr;
			}
		}

		return '';
	}

	private $_limit = array();

	/**
	 *查询大小设置
	 *
	 * @param      $start
	 * @param null $skip
	 *
	 * @return $this
	 */
	public function limit( $start, $skip = null ) {
		$this->_limit[0] = $start;
		$this->_limit[1] = $skip;

		return $this;
	}

	/**
	 *获取查询大小limit
	 *
	 * @return string
	 */
	public function getLimit() {
		$str = '';
		if ( $this->_limit ) {
			$str .= ' LIMIT ';
			isset( $this->_limit[0] ) && $this->_limit[0] !== null && $str .= $this->_limit[0];
			isset( $this->_limit[1] ) && $this->_limit[1] && $str .= ',' . $this->_limit[1];
			$str .= ' ';
		}

		return $str;
	}

	/**
	 * 查询的字段
	 *
	 * @var array
	 */
	private $_filed = array();

	/**
	 *查询字段
	 *
	 * @param      $field
	 * @param bool $isRemove
	 *
	 * @return $this
	 */
	public function field( $field, $isRemove = false ) {
		$this->_filed = $field;
		if ( $isRemove ) {
			$tmp = array();
			if ( is_array( $this->Fields ) ) {
				foreach ( $this->Fields as $filed_arr ) {
					$tmp[ $filed_arr['Field'] ] = $filed_arr['Field'];
				}
			}
			if ( is_string( $this->_filed ) ) {
				if ( isset( $tmp[ $this->_filed ] ) ) {
					unset( $tmp[ $this->_filed ] );
				}
			} else if ( is_array( $this->_filed ) ) {
				$tmp = array_diff( $tmp, $this->_filed );
			}
			$this->_filed = $tmp;
			unset( $tmp );
		}

		return $this;
	}

	/**
	 *获取查询字段
	 *
	 * @return array|string
	 */
	public function getSelectField() {
		if ( empty( $this->_filed ) ) {
			return ' * ';
		}
		if ( is_array( $this->_filed ) ) {
			return ' ' . implode( ',', $this->_filed ) . ' ';
		} else if ( is_string( $this->_filed ) ) {
			return $this->_filed;
		}
	}

	/**
	 *返回表名
	 *
	 * @return null|string
	 */
	public function getTable() {
		return $this->_table;
	}


	/**
	 * 获取所有数据库名
	 *
	 * @return array|bool
	 */
	public function getDBs() {

		$sql = 'show databases';
		$res = $this->exeCuteSqlData( $sql );

		$dbs = [];
		foreach ( $res as $row ) {
			$dbs[ $row['Database'] ] = $row['Database'];
		}

		return $dbs;
	}


	/**
	 * 执行sql
	 *
	 * @param $sql
	 *
	 * @return bool|\PDOStatement
	 */
	public function query( $sql ) {
		$this->addSqlHistory( $sql );
		$pdo = \PTLibrary\DB\Connect\PDO::getInstance()->getConnection();
		$res = $pdo->exec( $sql );
		PDO::getInstance()->close( $pdo );

		return $res;
	}

	/**
	 * @return string
	 * @throws \PTLibrary\DB\DBException
	 */
	public function getFullTableName() {
		if ( empty( $this->_table ) ) {
			throw new DBException( ErrorHandler::DB_TABLE_EMPTY );
		}
		if ( $this->_ignoreTablePrefix ) {
			$this->fullTableName = $this->_table;
		} else {
			$this->fullTableName = $this->table_prefix . $this->_table;
		}

		if ( $dbName = $this->getDbName() ) {
			$this->fullTableName = "`{$dbName}`.`{$this->fullTableName}`";
		}

		if ( $this->_table_as ) {
			return $this->fullTableName . ' as `' . $this->_table_as . '`';
		}


		return $this->fullTableName;
	}

	/**
	 *设置表名
	 *
	 * @param      $table
	 * @param null $as
	 *
	 * @return bool
	 */
	public function setTable( $table, $as = null ) {

		if ( $table ) {
			$this->_table = $table;
			$this->setTableAs( $as );
		}

		return false;
	}


	/**
	 *设置搜索条件
	 *
	 * @param null $where
	 *
	 * @return $this
	 */
	public function where( $where = null ) {
		$where && $this->_where = $where;

		return $this;
	}

	/**
	 * 返回记录数量
	 *
	 * @return false|int|mixed
	 * @throws \PTLibrary\DB\DBException
	 */
	public function count() {

		$where = $this->getWhere();
		$sql   = "select count(*) as count from " . $this->getFullTableName();
		if ( $where ) {
			$sql .= $where;
		}
		$sth = $this->exeCuteSqlData( $sql, $this->selectData );
		$res = $sth->fetch();
		$sth->closeCursor();

		return $res;

	}

	/**
	 * 删除数据
	 *
	 *
	 * @return false
	 * @throws \PTLibrary\DB\DBException
	 */
	public function delete() {
		$fullTable = $this->getFullTableName();
		$where     = $this->getWhere();
		if ( $where ) {
			$sql = "DELETE FROM $fullTable $where";
			$sth = $this->exeCuteSqlData( $sql, $this->selectData );
			$res = $sth->fetch();
			$sth->closeCursor();

			return $res;
		}

	}

	/**
	 * @param $data
	 *
	 * @return array|bool
	 * @throws \PTLibrary\Exception\DBException
	 */
	public function save( $data ) {
		$this->setPrepareData( $data );
		$where     = $this->getWhere();
		$whereData = $this->selectData;
		if ( is_array( $data ) ) {
			$data = array_merge( $data, $whereData );
		} else {
			return true;
		}
		$sql                    = "{$this->UpdatePrepare} $where";
		$this->_lastExecuteData = $data;
		$res                    = $this->exeCuteSqlData( $sql, $data, self::EXECUTE_TYPE_UPDATE );

		return $res;

	}



	public function beginTransaction(): void
	{
		if ($this->in_transaction) { //嵌套事务
			throw new DBException('do not support nested transaction now',200100);
		}
		$this->realGetConn();
		$this->_pdo->beginTransaction();
		$this->in_transaction = true;
		Coroutine::defer(function () {
			if ($this->in_transaction) {
				$this->rollBack();
			}
		});
	}

	public function commit(): void
	{
		$this->_pdo->commit();
		$this->in_transaction = false;
		$this->release($this->_pdo);
	}

	public function rollBack(): void
	{
		$this->_pdo->rollBack();
		$this->in_transaction = false;
		$this->release($this->_pdo);
	}

	private function realGetConn()
	{
		if (! $this->in_transaction) {
			$this->_pdo = $this->pool->getConnection();
		}
	}

	public function release($connection = null)
	{
		if ($connection === null) {
			$this->in_transaction = false;
		}

		if (! $this->in_transaction) {
			$this->pool->close($connection);
			return true;
		}

		return false;
	}

	/**
	 *
	 * 数据处理
	 * 对in条件的改进,支持字符串与数组 ，如何：where['id']=array('in'=>'1,2');where['id']=array('in'=>array(1,2,3));
	 *
	 * @param       $key
	 * @param array $value
	 *
	 * @return string
	 */
	public function ArrValue( $key, array $value ) {


		$key2       = key( $value );
		$value_     = $value[ $key2 ];
		$searchData = array();
		$_key       = ':w_' . str_replace( '.', '_', $key );
		switch ( $key2 ) {
			case'>':
				$reStr               = "$key > $_key";
				$searchData[ $_key ] = $this->valueForm( $key, $value_ );
				break;
			case'<':
				$reStr               = "$key < $_key";
				$searchData[ $_key ] = $this->valueForm( $key, $value_ );
				break;
			case'>=':
				$reStr               = "$key >= $_key";
				$searchData[ $_key ] = $this->valueForm( $key, $value_ );
				break;
			case'<=':
				$reStr               = "$key <= $_key";
				$searchData[ $_key ] = $this->valueForm( $key, $value_ );
				break;
			case'<>':
			case'!=':
				$reStr               = "$key <> $_key";
				$searchData[ $_key ] = $this->valueForm( $key, $value_ );
				break;
			case'in':
				$reStr = "$key ";
				if ( is_string( $value_ ) ) {
					$value_ = explode( ',', $value_ );
				}
				$in_v_keys = '';
				if ( is_array( $value_ ) ) {
					foreach ( $value_ as $in_key => $iv ) {
						$in_v_key                = ':in_' . $key . '_' . $in_key;
						$searchData[ $in_v_key ] = $iv;
						$in_v_keys               .= ( empty( $in_v_keys ) ? '' : ',' ) . $in_v_key;
					}
				}
				if ( $in_v_keys ) {
					$reStr = "$key in ( $in_v_keys )";
				}

				break;
			case'like':
				$reStr               = "$key like $_key ";
				$searchData[ $_key ] = $this->valueForm( $key, $value_ );
				break;
			case'expression': //表达式
				$reStr               = "$key = ( $_key )";
				$searchData[ $_key ] = $this->valueForm( $key, $value_ );
				break;
			case'between': //之间
				$_key1 = $_key . '1';
				$_key2 = $_key . '2';
				unset( $searchData[ $_key ] );
				$searchData[ $_key1 ] = $this->valueForm( $key, $value_[0] );
				$searchData[ $_key2 ] = $this->valueForm( $key, $value_[1] );
				$reStr                = "$key BETWEEN $_key1 AND $_key2 ";
				break;
			default:
				$reStr               = "$key = $_key ";
				$searchData[ $_key ] = $this->valueForm( $key, $value_ );
		}

		$result['prepare'] = $reStr;
		$result['data']    = $searchData;

		return $result;
	}


	/**
	 * @return string|null
	 */
	public function getWhere() {
		$this->selectData = [];
		if ( $this->_where ) {
			if ( is_array( $this->_where ) ) {
				$temp = array();
				foreach ( $this->_where as $key => $value ) {
					$_key = 'w_' . str_replace( '.', '_', $key );
					if ( is_array( $value ) ) {
						$tmp    = $this->ArrValue( $key, $value );
						$temp[] = $tmp['prepare'];
						foreach ( $tmp['data'] as $key3 => $data ) {

							$this->selectData[ $key3 ] = $data;
						}

					} else {
						$temp[]                          = " $key = :$_key ";
						$this->selectData[ ':' . $_key ] = $value;
					}
				}
				$where = implode( ' AND ', $temp );

				return ' WHERE ' . $where;
			} else if ( is_string( $this->_where ) ) {
				return $this->_where;
			}

		}

		return '';
	}

	/**
	 * 判断字段是否是int类型
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function fieldTypeIsInt( $field ) {
		$type = $this->getFieldTypeValue( $field );
		if ( $type ) {
			return in_array( $type, [ 'int', 'float', 'decimal', 'bigint', 'tinyint' ] );
		}

		return false;
	}

	/**
	 * 获取字段类型的值,开过滤‘()’
	 *
	 * @param $field
	 *
	 * @return bool|string
	 */
	public function getFieldTypeValue( $field ) {
		if ( ! isset( $this->Fields[ $field ] ) ) {
			return false;
		}
		$fieldsInfo = $this->Fields[ $field ];
		$type       = substr( $fieldsInfo['Type'], 0, stripos( $fieldsInfo['Type'], '(' ) );

		return $type;
	}

	/**
	 * 判断字段是允许为null
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function isEnableNull( $field ) {
		if ( ! isset( $this->Fields[ $field ] ) ) {
			return false;
		}

		$fieldsInfo = $this->Fields[ $field ];
		if ( is_array( $fieldsInfo ) && isset( $fieldsInfo['Null'] ) && strtolower( $fieldsInfo['Null'] ) == 'yes' ) {
			return true;
		}

		return false;
	}

	/**
	 * 数据类型转换
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function valueForm( $key, $value ) {
		$type = $this->getFieldTypeValue( $key );
		if ( $value && is_array( $value ) ) {
			$_a_key = $value[0];
			switch ( $_a_key ) {
				case 'expression':
					return $value[1];
			}
		}
		if ( ! $value && $this->isEnableNull( $key ) && is_null( Tool::getArrVal( 1, $value ) ) ) {
			return null;
		}
		$type = strtolower( $type );
		if ( $type ) {
			switch ( $type ) {
				case 'int':
					$value = intval( $value );
					break;
				case 'tinyint':
					$value = intval( $value );
					break;
				case'float':
					$value = floatval( $value );
					break;
				case'decimal':
					$value = floatval( $value );
					break;
				case 'varchar':
					$value = (string) $value;
					break;
				case 'char':
					$value = (string) $value;
					break;
				case 'text':
					$value = (string) $value;
					break;

			}
		}

		return $value;
	}


}