<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/9/21
 * Time: 16:40
 */

namespace DB\Mongodb;


use Error\ErrorHandler;
use Exception\ThrowException;
use Log\Log;
use Tool\Tool;

class MongodbBase
{
	/**
	 * @var \MongoDB\Driver\Manager
	 */
	private $m;

	function __construct()
	{
		$this->connect();
	}

	/**
	 * 实体实例
	 *
	 * @var MongodbEntity
	 */
	protected $_entity = null;

	protected $_count = 0;

	/**
	 * @var \MongoDB
	 */
	protected $db;
	/**
	 * @var \MongoCollection
	 */
	protected $_collection;
	/**
	 * 查询条件
	 *
	 * @var array
	 */
	protected $_where = [];

	protected $_ResIndexField='';
	/**
	 * 排序
	 *
	 * @var array
	 */
	protected $_order = [];
	/**
	 * 分组
	 *
	 * @var array
	 */
	protected $_group = [];
	/**
	 * 查询分页
	 *
	 * @var array
	 */
	protected $_limit = [];
	/**
	 * 数据库名
	 *
	 * @var string
	 */
	protected $_dbName = '';
	/**
	 * 集合文件，或表名
	 *
	 * @var string
	 */
	protected $_table = '';
	/**
	 * 查询字段
	 *
	 * @var array
	 */
	protected $_field = [];

	private $config = [];
	/**
	 * 是否允许启用分页
	 *
	 * @var bool
	 */
	protected $_permitPage = false;
	/**
	 * 分页大小
	 *
	 * @var int
	 */
	protected $_pageSize = 20;
	/**
	 * 当前页
	 *
	 * @var int
	 */
	protected $_currentPage = 1;
	/**
	 * 过滤选项
	 *
	 * @var array
	 */
	protected $_projection = [];

	public function connect($isReconnect=false)
	{
		$this->m = MongodbConnect::getConnect($isReconnect);
	}

	private function reconnect() {
		$this->m = MongodbConnect::getConnect(true);
	}
	/**
	 * 条件设置
	 *
	 * <pre>
	 * $lt          <
	 * $lte         <=
	 * $gt          >
	 * $gte         >=
	 * $ne          !=
	 * $in          包含
	 * $nin         不包含
	 * $or          或查询
	 * $all         匹配所有
	 * $exists      判断文档属性是否存在
	 * $not元条件    不在什么范围之前 db.B.find({"age":{"$not":{"$mod":[5,1]}}})
	 * $regex       正则表达式 $where[ $type ] = ['$regex'=>'.*'.$keyword.'.*'];
	 * $size        数组长度，db.C.find({"b":{"$size":2}}) 查询b数组长度=2
	 *
	 * </pre>
	 *
	 * @param array $where
	 *
	 * @return $this
	 */
	function where( array $where )
	{
		$this->_where = $where;

		return $this;
	}

	/**
	 * 设置体
	 *
	 * @param \DB\Mongodb\MongodbEntity $entityBase
	 */
	public function setEntity( MongodbEntity $entityBase )
	{
		$this->_entity = $entityBase;
	}

	/**
	 * 查询结果总数量
	 * 返回的是上一次 getAll查询后的总数量
	 *
	 * @return int
	 */
	function getCount()
	{
		return $this->_count;
	}

	/**
	 * 是否开启分页
	 *
	 * @param boolean $_permitPage
	 */
	public function setPermitPage( $_permitPage )
	{
		$this->_permitPage = $_permitPage;
	}

	/**
	 * @return int
	 */
	public function getPageSize()
	{
		return $this->_pageSize;
	}

	/**
	 * @param int $pageSize
	 */
	public function setPageSize( $pageSize )
	{
		$this->_pageSize = $pageSize;
	}

	/**
	 * @return int
	 */
	public function getCurrentPage()
	{
		return $this->_currentPage;
	}

	/**
	 * @param int $_currentPage
	 */
	public function setCurrentPage( $_currentPage )
	{
		$this->_currentPage = $_currentPage;
	}


	/**
	 * 选择数据库
	 *
	 * @param $dbName
	 *
	 * @return $this
	 */
	function selectDB( $dbName = null )
	{
		if ( $dbName ) {
			$this->_dbName = $dbName;
		}

		return $this;
	}

	/**
	 * 设置数据库名
	 *
	 * @param $_dbName
	 *
	 * @return $this
	 */
	function setDBName( $_dbName )
	{
		if ( $_dbName ) {
			$this->_dbName = $_dbName;
		}
		$this->selectDB();

		return $this;
	}


	/**
	 * 设置查询字段
	 *
	 * @param array $field
	 *
	 * @return $this
	 */
	function field( array $field , $is_projection = false )
	{
		if ( $field ) {
			$this->_field      = $field;
			$this->_projection = [];
			if ( !isset($this->_field['_id']) && !$is_projection ) {
				$this->_projection['_id'] = 0;
			}

			foreach ( $field as $row ) {
				$this->_projection[$row] = $is_projection ? 0 : 1;
			}
		}

		return $this;
	}

	/**
	 * @return \MongoDB\Driver\Manager
	 */
	public function getM()
	{
		return $this->m;
	}

	/**
	 * @return string
	 */
	public function getDbName()
	{
		return $this->_dbName;
	}

	/**
	 * @return string
	 */
	public function getTable()
	{
		return $this->_table;
	}

	/**
	 * @param string $_table
	 *
	 * @return $this
	 */
	public function setTable( $_table )
	{
		$this->_table = $_table;

		return $this;
	}

	/**
	 * 查询记录
	 *
	 * @return bool
	 */
	function getOne()
	{
		$res = $this->limit( 1 , 0 )->select();
		if ( $res ) {
			return current($res);
		}

		return false;
	}

	/**
	 * 通过id获取数据
	 *
	 * @param $_id
	 *
	 * @return array|bool|null
	 */
	function getById( $_id )
	{
		if ( $_id ) {
			$res = $this->where( ['_id' => $_id] )->getOne();

			return $res;
		}

		return false;
	}

	/**
	 * 分页
	 *
	 * @param int $size  每页显示条数
	 * @param int $start 第几条开始算起
	 *
	 * @return $this
	 */
	function limit( $size , $start = 0 )
	{
		$this->_limit = [$size , $start];

		return $this;
	}

	/**
	 * 取每页显示条数
	 *
	 * @return bool|int
	 */
	private function getLimit()
	{
		$limit = false;
		if ( $this->_permitPage ) {
			$limit = $this->_pageSize;
		}
		elseif ( $this->_limit ) {
			$limit = (isset($this->_limit[0]) && $this->_limit[0]) ? intval( $this->_limit[0] ) : 20;
		}

		return $limit;
	}

	private function getSkip()
	{
		$skip = false;
		if ( $this->_permitPage ) {
			$skip = ($this->_currentPage - 1) * $this->_pageSize;
		}
		elseif ( $this->_limit ) {
			$skip = (isset($this->_limit[1]) && $this->_limit[1]) ? intval( $this->_limit[1] ) : 0;
		}

		return $skip;
	}


	/**
	 * 获取所有满足条件的记录
	 *
	 * @return array
	 */
	function getAll()
	{
		if ( !$this->_collection ) ThrowException::MongodbException( ErrorHandler::DB_TABLE_EMPTY , '没有集合对象' );
		$cursor       = $this->_collection->find( $this->_where , $this->_field );
		$this->_count = $cursor->count();
		if ( $this->_order ) {
			$cursor->sort( $this->_order );
		}
		$limit = $this->getLimit();
		$cursor->skip( $limit * ($this->_currentPage - 1) );
		$limit && $cursor->limit( $limit );
		$return = [];
		foreach ( $cursor as $val ) {
			$return[] = $val;
		}
		$this->clearWhere();

		return $return;
	}


	/**
	 * 删除记录
	 *
	 * @param array $where 条件
	 *
	 * @return bool
	 */
	function delete( $where = [] )
	{
		$where && $this->where( $where );
		if ( !empty($this->_where) ) {
			$bulk = new \MongoDB\Driver\BulkWrite();
			$bulk->delete( $this->_where );
			$db_info      = $this->getDbTable();
			$writeConcern = new \MongoDB\Driver\WriteConcern( \MongoDB\Driver\WriteConcern::MAJORITY , 1000 );
			$res          = $this->executeBulkWrite( $db_info , $bulk , $writeConcern );
			unset($bulk);
			$this->clearWhere();

			return $res->getDeletedCount() ? true : false;
		}

		return false;
	}

	/**
	 * 清空表
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function clear()
	{
		$bulk = new \MongoDB\Driver\BulkWrite;
		$bulk->delete( [] );
		$db_info = $this->getDbTable();
		$res     = $this->executeBulkWrite( $db_info , $bulk );
		unset($bulk);

		return $res->getDeletedCount() ? true : false;
	}

	/**
	 * 清空查询条件
	 */
	function clearWhere()
	{
		$this->_where      = [];
		$this->_field      = [];
		$this->_limit      = [];
		$this->_order      = [];
		$this->_group      = [];
		$this->_projection = [];

		return $this;
	}

	public function setResIndexField($field){
		$this->_ResIndexField = $field;

		return $this;
	}

	private function getDbTable()
	{
		$dbName = $this->getDbName();
		if ( empty($dbName) ) {
			throw new \Exception( '数据库名为空' );
		}
		$dbTable = $this->getTable();
		if ( empty($dbTable) ) {
			throw new \Exception( '表名为空' );
		}

		return $dbName . '.' . $dbTable;
	}

	/**
	 *
	 * @param      $data
	 * @param bool $multi  true：变量更新  false：单个更新
	 * @param bool $upsert true：如果不存在则添加  false：不添加
	 *
	 * @return bool
	 * @throws \Exception
	 */
	function update( $data , $multi = true , $upsert = false )
	{

		$bulk = new \MongoDB\Driver\BulkWrite;
		$inc = Tool::getArrVal( '$inc', $data );
		unset($data['$inc']);
		$bulk->update( $this->_where , ['$set' => $data] , ['multi' => $multi , 'upsert' => $upsert] );

		$db_info = $this->getDbTable();
		//$writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
		$res          = $this->executeBulkWrite( $db_info , $bulk );
		if($inc){
			$bulk->update( $this->_where , ['$inc'=>$inc] , ['multi' => $multi , 'upsert' => $upsert] );
			$res          = $this->executeBulkWrite( $db_info , $bulk );
		}
		$updateNumber = $res->getModifiedCount();
		//Log::log( '$updateNumber=' . $updateNumber );
		unset($bulk , $writeConcern , $res);
		$this->clearWhere();

		return $updateNumber !== false ? true : false;
	}

	final public function executeBulkWrite($namespace,  $bulk,  $writeConcern = null)
	{
		try{
			return $this->m->executeBulkWrite($namespace,$bulk,$writeConcern);
		}catch(\MongoDB\Driver\Exception\ConnectionTimeoutException $e){
			Log::log( 'mongodb 超时' );
			$this->reconnect();
		}

	}
	/**
	 * 保存数据，ID 不存在，则添加,ID是mongodb自生成
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	function save( $data )
	{
		$_id = Tool::getArrVal( '_id' , $data );
		if ( $_id ) {
			$res = $this->where( ['_id' => $_id] )->update( $data );
		}
		else {
			$res = $this->add( $data );
		}

		return $res;
	}

	/**
	 * 数据处理,自动类型转化
	 *
	 * @param $data
	 */
	public function pretreatmentData( &$data )
	{

	}

	/**
	 * 添加数据，自生成ID
	 *
	 * @param array $data
	 *
	 * @return bool|string
	 */
	function add( array $data )
	{
		$data['_id'] = md5(new \MongoDB\BSON\ObjectID().Tool::getRandChar(32));

		$bulk        = new \MongoDB\Driver\BulkWrite;
		$bulk->insert( $data );
		$db_info      = $this->getDbTable();
		$writeConcern = new \MongoDB\Driver\WriteConcern( \MongoDB\Driver\WriteConcern::MAJORITY , 1000 );
		$res          = $this->executeBulkWrite( $db_info , $bulk );
		if(!$res){
			return false;
		}
		$n            = $res->getInsertedCount();
		unset($bulk , $res , $writeConcern);
		$this->clearWhere();

		return $n ? $data['_id'] : false;
	}

	/**
	 * 排序
	 *
	 * @param array $order
	 * ['id'=> 1] 按id正排序
	 * ['id'=> -1] 按id倒排序
	 *
	 * @return $this;
	 */
	function order( array $order )
	{
		$this->_order = $order;

		return $this;
	}

	/**
	 * 分组
	 *
	 * @param array $group
	 *
	 * @return $this;
	 */
	function group( array $group )
	{
		$this->_group = $group;

		return $this;
	}


	/**
	 * 指量添加
	 *
	 * @param array $data
	 *
	 * @return bool|int
	 */
	function addAll( array $data )
	{
		$num = 0;
		foreach ( $data as $val ) {
			if ( $this->add( $val ) !== false ) $num++;
		}
		if ( $num > 0 ) {
			return $num;
		}

		return false;
	}

	function select( $filter = array() )
	{
		try{
			$options = [];
			if ( $this->_order ) {
				$options['sort'] = $this->_order;
			}
			if ( $this->_limit || $this->_permitPage ) {
				$options['limit'] = $this->getLimit();
				$options['skip']  = $this->getSkip();
			}
			if ( $this->_projection ) {
				$options['projection'] = $this->_projection;
			}

			$filter && $this->where( $filter );
			$this->filedTypeVerify( $this->_where );
			$query   = new \MongoDB\Driver\Query( $this->_where , $options );
			$start=Tool::microtime_float();
			$db_info = $this->getDbTable();
			$rows    = $this->m->executeQuery( $db_info , $query ); // $mongo contains the connection object to MongoDB
			$array   = array();

			//Log::log( $this->_where );
			foreach ( $rows as $row ) {
				$row=(array)$row;
				if($this->_ResIndexField && isset($row[$this->_ResIndexField])){
					$array[$row[$this->_ResIndexField]] = $row;
				}else{
					$array[] =  $row;
				}
			}
			//Log::log( 'mongodb 查询: ' . $db_info.'   用时: '.(Tool::microtime_float()-$start).print_r($this->_where,true) );
			$this->clearWhere();
			unset($query , $rows);
			if ( empty($array) ) return false;
			$this->doRelevance( $array );//关联查询
			return $array;
		}catch(\MongoDB\Driver\Exception\ConnectionTimeoutException $e){
			$this->reconnect();
		}catch(\Exception $e){
			if(strpos($e->getMessage(),'No suitable servers')!==false){
				$this->reconnect();
			}
		}

	}

	/**
	 * @param $data
	 */
	public function doRelevance( &$data )
	{
		if ( $this->_entity ) {
			$this->_entity->doRelevance( $data );
		}
	}

	/**
	 * 字段类型自动转换
	 *
	 * @param $data
	 */
	public function filedTypeVerify( &$data )
	{
		if ( $this->_entity && $data && is_array( $data ) ) {
			$this->_entity->getContainer()->pretreatmentData( $data );
		}
	}

	/**
	 * 按字段名递增，$field，是数组时,多个字段递增
	 *
	 * $field=['field'=>3,'filed2'=>2]
	 *
	 * @param  string $field 字段名
	 * @param int     $step  添加的数量
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function setInc( $field , $step = 1 )
	{
		$bulk = new \MongoDB\Driver\BulkWrite;
		$_update = [];
		if(is_array($field)){
			$_update=['$inc' => $_update] ;
		}else{
			$_update=['$inc' => [$field => $step]];
		}
		$bulk->update( $this->_where , $_update, ['multi' => false , 'upsert' => false] );

		$db_info      = $this->getDbTable();
		$writeConcern = new \MongoDB\Driver\WriteConcern( \MongoDB\Driver\WriteConcern::MAJORITY , 1000 );
		$res          = $this->executeBulkWrite( $db_info , $bulk , $writeConcern );
		$this->clearWhere();
		$n = $res->getInsertedCount();
		unset($bulk , $res , $writeConcern);

		return $n ? true : false;
	}

	/**
	 * 按字段名减少数量
	 *
	 * @param  string   $field 字段名
	 * @param int $step  减少的数量
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function setDnc( $field , $step = 1 )
	{
		$bulk = new \MongoDB\Driver\BulkWrite;
		$bulk->update( $this->_where , ['$inc' => [$field => ($step * -1)]] , ['multi' => false , 'upsert' => false] );
		$db_info      = $this->getDbTable();
		$writeConcern = new \MongoDB\Driver\WriteConcern( \MongoDB\Driver\WriteConcern::MAJORITY , 1000 );
		$res          = $this->executeBulkWrite( $db_info , $bulk , $writeConcern );
		$this->clearWhere();
		$nInserted = $res->getInsertedCount();
		unset($bulk , $writeConcern , $res);

		return $nInserted ? true : false;
	}

	/**
	 * 按条件查数量
	 */
	public function count( $where = [] )
	{
		$where && $this->where( $where );
		$dbName = $this->getDbName();
		if ( empty($dbName) ) {
			throw new \Exception( '数据库名为空' );
		}
		$dbTable = $this->getTable();
		if ( empty($dbTable) ) {
			throw new \Exception( '表名为空' );
		}
		$command = new \MongoDB\Driver\Command( [
			'count' => $dbTable ,//表名
			'query' => $this->_where
		] );
		$cursor  = $this->m->executeCommand( $dbName , $command );
		$result  = (array) $cursor->toArray()[0];
		unset($command);

		return $result['n'];
	}
}