<?php

namespace PTLibrary\DB;




use PTLibrary\Error\ErrorHandler;
use PTLibrary\Exception\ThrowException;
use PTLibrary\Log\Log;
use PTLibrary\Tool\Request;
use SebastianBergmann\CodeCoverage\Report\PHP;

/**
 * 数据模型基类
 * Class BaseModel
 */
class BaseM extends IModelInterface {

	public $fieldArr = null;
	public $FieldData = array();

	/**
	 * 是否开启分页查询功能，默认不开
	 *
	 * @var bool
	 */
	public $pageEnable = false;

	private $_recordCount = null;
	/**
	 * 分页链接key
	 *
	 * @var string
	 */
	private $_pageUrl_key = 'page';
	private $_page_size_key = 'pagesize';
	/**
	 * 数据库名称
	 *
	 * @var string
	 */
	protected $DBName = '';
	/**
	 * 当前页
	 *
	 * @var int
	 */
	protected $_currentPage = 1;
	/**
	 * 分页大小
	 *
	 * @var int
	 */
	protected $_pageSize = 20;

	/**
	 * 是否忽略表前缀
	 *
	 * @var bool
	 */
	public $_ignoreTablePrefix = false;

	public $_tablePrefix = null;
	/**
	 * 是否获取总条数,有时候join 查询时，获取总量时就有错误，此时就可以设置为 false
	 * @var bool
	 */
	public $_isGetTotalPage=true;

	/**
	 * @var MysqlEntity
	 */
	public $_entity;

	function __construct( $table = null ) {
		$table && $this->_table = $table;
		$this->_table && $this->setTable( $this->_table );


	}

	public function init() {
		$this->PDO=PtPDO::getInstance();

	}

	private static $instance;

	public static function instance(){
	    if(is_null(self::$instance)){
	        self::$instance=new static();
	    }
	    return clone self::$instance;
	}


	/**
	 * 设置不存在的属性
	 *
	 * 与下划线开头的属性统一为模型的属性
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set( $name, $value ) {
		if ( substr( $name, 0, 1 ) == '_' ) {
			$this->$name = $value;
		} else {
			$this->FieldData[ $name ] = $value;
		}
	}

	public function __destruct() {

		unset( $this->PDO );
		unset( $this->_entity );
	}

	public function __clone() {

		$this->init();

	}

	public function __get( $name ) {

		if ( isset( $this->FieldData[ $name ] ) ) {
			return $this->FieldData[ $name ];
		}

		return null;
	}


	/**
	 * @param int $currentPage
	 */
	public function setCurrentPage( $currentPage ) {
		if ( ! $currentPage ) {
			$currentPage = 1;
		}
		$this->_currentPage = $currentPage;
	}

	/**
	 * 总页数
	 *
	 * @return float|int
	 * @throws \Exception
	 */
	public function getTotalPage() {
		if(!$this->_isGetTotalPage)return null;
		$count            = $this->count();
		$pageSize         = $this->getPageSize();
		$this->_totalPage = ceil( $count / $pageSize );

		return $this->_totalPage;
	}

	/**
	 * 获取当前页
	 *
	 * @return bool|null
	 */
	public function getCurrentPage() {
		$page = (int) Request::instance()->get($this->_pageUrl_key);
		$page || $page = $this->_currentPage;
		$this->getTotalPage();
		if ( $page <= 0 ) {
			$page = 1;
		} else if ( $this->_totalPage && $page > $this->_totalPage ) {
			$page = $this->_totalPage;
		}
		$this->_currentPage = $page;

		return $page;
	}

	/**
	 * 获取每页记录大小
	 *
	 * @return int
	 */
	public function getPageSize() {
		$pageSize = (int) Request::instance()->get($this->_page_size_key,$this->_pageSize);
		$pageSize || $pageSize = 20;

		return $pageSize;
	}

	protected $_totalPage = 0;

	/**
	 * 获取分页信息
	 *
	 * @return mixed
	 */
	public function getPageInfo() {
		$info['current']     = $this->getCurrentPage();
		$info['pageSize']    = $this->_pageSize;
		$info['countRecord'] = $this->_recordCount;
		$info['totalPage']   = $this->_totalPage;
		$this->_currentPage  = $info['current'];

		return $info;
	}
	/**
	 * @param null $url
	 *
	 * @return $this
	 */
	/*function setPageUrl($url=null)
	{
		if($url){
			$this->_pageUrl = $url;
		}else{
			$param= $_GET;
			unset($param[$this->_pageUrl_key]);
			$this->_pageUrl = Route::instance()->U('', $param);
		}

		return $this;
	}*/


	/**
	 * 设置是否开启自动验证字段
	 *
	 * @param boolean $DoVerifyRule
	 */
	public function setDoVerifyRule( $DoVerifyRule ) {
		$this->DoVerifyRule = $DoVerifyRule;
	}

	/**
	 * 设置表格
	 *
	 * @param $table
	 *
	 * @return $this
	 * @throws DBException
	 */
	public function setTable( $table ) {

		$table && $this->_table = $table;

		if ( $this->_table ) {

			$this->PDO->setTable( $this->_table );

			if ( $this->PDO->table_exists() ) {
				$this->getFieldArr();
				$this->fullTableName || $this->fullTableName = $this->PDO->getFullTableName();
				$this->PK = $this->PDO->PK;
			} else {
				ThrowException::DBException( ErrorHandler::DB_TABLE_EXIST, $this->PDO->getTable() . '不存在' );
			}
		}

		return $this;
	}

	/**
	 *
	 * @throws \PTLibrary\DB\DBException
	 */
	public function getFieldArr() {
		$this->fieldArr = $this->PDO->getFields();

		return $this->fieldArr;
	}

	/**
	 * @param bool $value
	 */
	public function ignoreTablePrefix( $value = true ) {
		$this->_ignoreTablePrefix      = $value;
		$this->PDO->_ignoreTablePrefix = $value;
		//$this->getFullTableName();
	}

	public function getTableName() {
		return $this->_table;
	}

	/**
	 * 设置实体实例对象
	 *
	 * @param \PTLibrary\DB\MysqlEntity $mysqlEntity
	 *
	 * @return $this
	 */
	public function setEntity( MysqlEntity $mysqlEntity ) {
		$this->_entity = $mysqlEntity;
		$pk            = $this->PK;
		$this->PKV     = $this->_entity->$pk;

		return $this;
	}

	public function getDBName() {
		if ( ! $this->DBName ) {
			$this->DBName = $this->PDO->getDbName();
		}

		return $this->DBName;
	}

	public function setDBName( $dbName ) {
		$this->DBName = $dbName;
		$this->PDO->setDbName( $dbName );

		return $this;
	}

	/**
	 * 获取所有数据库表名
	 *
	 * @return array|null
	 */
	public function getTables() {
		return $this->PDO->getAllTables();
	}

	/**
	 * 获取所有数据库名
	 *
	 * @return array|bool
	 */
	public function getDBs() {
		return $this->PDO->getDBs();
	}

	/**
	 * 设置字段的值
	 *
	 * @param $field
	 * @param $value
	 *
	 * @return bool
	 */
	public function setField( $field, $value = null ) {
		if ( is_string( $field ) ) {
			$this->FieldData[ $field ] = $value;
		} else if ( is_array( $field ) ) {
			$this->FieldData = array_merge( $this->FieldData, $field );
		} else {
			return false;
		}

		return true;
	}

	/**
	 * 设置主键值
	 *
	 * @param string $PKV
	 */
	public function setPKV( $PKV ) {
		$this->PKV = $PKV;
	}

	/**
	 * 返回主键值
	 *
	 * @return string
	 */
	public function getPKV() {
		return $this->PKV;
	}

	/**
	 * 返回主键字符串
	 *
	 * @return string
	 */
	public function getPK() {
		return $this->PK;
	}

	/**
	 * 设置查询大小
	 *
	 * @param      $start
	 * @param bool $pageSize
	 *
	 * @return $this
	 */
	public function limit( $start, $pageSize = false ) {
		$this->PDO->limit( $start, $pageSize );

		return $this;
	}

	public function sleep($time=0){
		$this->PDO->sleep( $time );
		return $this->PDO;
	}

	/**
	 * 插入数据
	 *
	 *
	 * @param array $data
	 * @param bool  $is_move_pk  是否强制删除主键值,对于自增id主键时，可以为true
	 * @param bool  $filter_null 是否过滤null 值的字段,true时为过滤
	 *
	 *
	 * @return bool|mixed
	 * @throws \Exception\DBException
	 */
	public function add( $data = array(), $is_move_pk = false, $filter_null = false ) {
		$this->setFieldData( $data );
		if ( $is_move_pk ) {
			unset( $this->FieldData[ $this->PK ] );
		}
		$this->VerifyRule();
		$id=$this->PDO->add( $this->FieldData, $filter_null );
		if($id===false){
			ThrowException::SystemException(ErrorHandler::DB_INSERT_FAIL);
		}else {
			$this->addAfter( $id );
		}

		return $id;
	}

	/**
	 * 添加成功后处理
	 *
	 * @param $insert_id
	 */
	public function addAfter( $insert_id ) {

	}

	/**
	 * 获取错误信息
	 *
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->Error_Message;
	}

	/**
	 * 设置字段值
	 *
	 * @param array $data
	 */
	public function setFieldData( $data = array() ) {

		if ( is_array( $data ) ) {
			$this->FieldData = $data;
		}
		if ( isset( $this->FieldData[ $this->PK ] ) && $this->FieldData[ $this->PK ] ) {
			$this->setPKV( $this->FieldData[ $this->PK ] );
		} else {
			$this->FieldData = array_merge( $this->_DefaultFieldValue, $this->FieldData );
		}
		foreach ( $this->FieldData as $key => $val ) {
			if ( ! isset( $this->fieldArr[ $key ] ) ) {
				unset( $this->FieldData[ $key ] );
			}
		}
	}

	/**
	 * 设置查询字段
	 * field(array('SUN(number)'))
	 *
	 * @param            $field
	 * @param bool|FALSE $remove
	 *
	 * @return $this
	 * @throws DBException
	 */
	public function field( $field, $remove = false ) {

		if ( empty( $this->PDO ) ) {
			throw new DBException( ErrorHandler::DB_PDO_EMPTY );
		}
		$this->PDO->field( $field, $remove );

		return $this;
	}

	/**
	 * 设置控制器
	 *
	 * @param $action
	 */
	public function setAction( $action ) {
		if ( $action ) {
			$this->action = $action;
		}
	}

	/**
	 * 获取表的字段详细数据
	 *
	 * @return array
	 */
	public function getFieldData() {
		return $this->FieldData;
	}

	/**
	 * 开启关联关系
	 *
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function relevance( $value = true ) {
		$this->_relevance = $value;

		return $this;
	}

	/**
	 *  通过数组一次插入多条数据
	 *
	 * @param array $data
	 *
	 * @return bool
	 * @throws \PTLibrary\Exception\DBException
	 */
	public function addAll( $data = array() ) {
		foreach ( $data as $_data ) {
			$this->PDO->add( $_data );
		}

		return true;
	}

	/**
	 *  通过主键获取数据信息
	 *
	 * @param $pk_value
	 *
	 * @return mixed
	 */
	public function getByPK( $pk_value ) {
		$info      = $this->PDO->where( array( $this->PK => $pk_value ) )->find();
		$this->PKV = $pk_value;

		return $info;
	}

	/**
	 * 设置查询条件
	 *
	 * <pre>
	 * 表达式:array('>'=>value)
	 * 条件有:
	 * >
	 * >=
	 * <
	 * <=
	 * != 相当 <>
	 * in
	 * like  array('like'=>'%'.value.'%')
	 *
	 * expression
	 * between
	 * $where['id']=>array('>'=>value)
	 *  对in条件的改进,支持字符串与数组 ，
	 * 如何：where['id']=array('in'=>'1,2');
	 * where['id']=array('in'=>array(1,2,3));
	 * where['id']=array('between'=>array(1,29))
	 * </pre>
	 *
	 * @param $where
	 *
	 * @return $this
	 */
	public function where( $where ) {
		if ( $where ) {
			$this->PDO->where( $where );
		}

		return $this;
	}

	/**
	 *通过某个字段来查找一条数据
	 *
	 * @param $Attribute
	 * @param $value
	 *
	 * @return bool|mixed
	 */
	public function findByAttribute( $Attribute, $value ) {

		return $this->where( array( $Attribute => $value ) )->find();
	}

	/**
	 *  通过某个字段来查找多条数据
	 *
	 * @param $Attribute
	 * @param $value
	 *
	 * @return mixed
	 * @throws \PTLibrary\DB\DBException
	 */
	public function findAllByAttribute( $Attribute, $value ) {
		return $this->where( array( $Attribute => $value ) )->select();
	}

	/**
	 *  设置分类大小
	 *
	 * @param int $size
	 *
	 * @return $this
	 */
	public function setPageSize( $size = 20 ) {
		$this->_pageSize = $size;

		return $this;
	}

	/**
	 * @param $field
	 *
	 * @return $this
	 */
	public function setResIndexField( $field ) {
		$this->PDO->setResIndexField( $field );

		return $this;
	}

	/**
	 * 排序 如array('id'=>1) 1:大到小,-1 小到大
	 *
	 * @param $order
	 *
	 * @return $this
	 */
	public function order( $order ) {
		$this->PDO->order( $order );

		return $this;
	}

	/**
	 * 分组
	 *
	 * @param $group
	 *
	 * @return $this
	 */
	public function group( $group ) {
		$this->PDO->group( $group );

		return $this;
	}

	/**
	 *  设置开启分页
	 *
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function setPageEnable( $value = true ) {
		$this->pageEnable = $value;

		return $this;
	}

	/**
	 * 记录总数
	 *
	 *
	 * @return bool|int|null
	 * @throws \Exception
	 */
	public function count() {
		$this->_recordCount = $this->PDO->count();

		return $this->_recordCount;
	}

	/**
	 *清空查询的条件
	 */
	public function clearCondition() {
		$this->PDO->clearCondition();
	}

	/**
	 * 链接方法
	 *
	 * @param string $table 关联表名
	 * @param null   $as    表别名
	 * @param null   $on    on条件
	 * @param null   $filed 查询的关联表字段
	 *
	 * @return $this\
	 */
	public function join( $table, $as = null, $on = null, $filed = null ) {
		$this->PDO->join( $table, $as, $on, $filed );

		return $this;
	}


	/**
	 * @param $as
	 *
	 * @return $this
	 */
	public function tableAs( $as ) {
		$this->PDO->setTableAs( $as );

		return $this;
	}


	/**
	 * 获取全表名
	 *
	 * @return string
	 * @throws \PTLibrary\DB\DBException
	 */
	public function getFullTableName() {
		return $this->PDO->getFullTableName();
	}

	/**
	 *  查询多条数据
	 *
	 * @param array $where
	 *
	 * @throws DBException
	 * @return mixed
	 */
	/**
	 * @param array $where
	 *
	 * @return array
	 * @throws \PTLibrary\DB\DBException
	 */
	public function select( $where = array() ) {
		try {
			$this->where( $where );
			if ( $this->pageEnable ) {
				$res = $this->PDO->limit( ( $this->_currentPage - 1 ) * $this->getPageSize(), $this->getPageSize() )->select();
			} else {
				$res = $this->PDO->select();
			}
			//实体关联查询
			if ( $this->_entity ) {
				$this->_entity->doRelevance( $res );
			}

			return $res;
		} catch ( \Exception $e ) {
			Log::error( $e->getMessage() . ';' . $this->PDO->getLastSql() );
			throw new DBException( ErrorHandler::DB_SELECT_FAIL );
		}

	}

	/**
	 * 随机读取记录
	 *
	 * @param bool $isRand
	 *
	 * @return $this
	 */
	public function rand($isRand=true){
		$this->PDO->rand( $isRand );
		return $this;
	}


	/**
	 * 分页查询
	 *
	 * 返回: array('data'=>'','page'=>'')
	 *
	 * @param null $size
	 * @param bool $notGetCount
	 *
	 * @return mixed
	 * @throws \PTLibrary\DB\DBException
	 */
	public function findPage( $size = null ,$notGetCount=true) {

		$size || $size =Request::instance()->get( $this->_page_size_key );
		$size || $size = 20;
		$this->_isGetTotalPage =$notGetCount;
		$this->pageEnable = true;
		$this->setPageSize( $size );
		$list['page'] = $this->getPageInfo();
		$res          = $this->select();
		$list['data'] = $res;
		unset( $res );

		return $list;
	}


	/**
	 *  返回显示分页列表
	 *
	 * @return null
	 */
	public function showPage() {
		return $this->_pageInfo;
	}

	/**
	 * 查找单条数据
	 *
	 * @param null $where
	 *
	 * @return false|mixed
	 * @throws \PTLibrary\DB\DBException
	 */
	public function find( $where = null ) {

		$this->where( $where );

		$this->limit( 1 );
		$info = $this->select();
		if ( $info ) {
			return current( $info );
		}

		return false;
	}


	/**
	 * 设置表格
	 *
	 * @param      $table
	 * @param null $as
	 *
	 * @return $this
	 */
	public function table( $table, $as = null ) {
		$this->PDO->setTable( $table, $as );

		return $this;
	}

	/**
	 * 多对一关联查询处理
	 *
	 * @param $child_name
	 * @param $relevance
	 * @param $dimension
	 */
	function manyToOne( $child_name, $relevance, $dimension ) {
		if ( $child_name && $relevance && $relevance['table'] && $this->relevanceResource ) {

			$relevance_Obj = D( $relevance['table'] );
			if ( $relevance_Obj ) {
				if ( $dimension == 1 ) {
					$this->relevanceResource[ $child_name ] = $this->doGetRelevance( $relevance_Obj, 'find', $relevance );;
				} elseif ( $dimension == 2 ) {
					foreach ( $this->relevanceResource as $key => $relevance_val ) {
						$this->relevanceResource[ $key ][ $child_name ] = $this->doGetRelevance( $relevance_Obj, 'select', $relevance,
							$key );

					}
				}
			}
		} else {

		}
	}

	/**
	 *  一对一
	 *
	 * @param $child_name
	 * @param $relevance
	 * @param $dimension
	 */
	/* function oneToOne($child_name, $relevance, $dimension)
	 {

		 if ($child_name && $relevance && $relevance['table'] && $this->relevanceResource) {
			 $relevance_Obj = D($relevance['table']);

			 if ($relevance_Obj) {
				 if ($dimension == 1) {
					 $this->relevanceResource[$child_name] = $this->doGetRelevance($relevance_Obj, 'find', $relevance);
				 } elseif ($dimension == 2) {
					 foreach ($this->relevanceResource as $key => $val) {
						 $this->relevanceResource[$key][$child_name] = $this->doGetRelevance($relevance_Obj, 'find', $relevance, $key);
					 }
				 }
			 }
		 } else {
			 waringLog("\$relevance_Obj = null ", __FILE__, __LINE__);
		 }
	 }*/

	/**
	 *  多对多关联
	 */
	/*function manyToMany($child_name, $relevance, $dimension)
	{
		if ($child_name && $relevance && $relevance['table'] && $this->relevanceResource) {
			$relevance_Obj = D($relevance['table']);
			if ($relevance_Obj) {
				foreach ($this->relevanceResource as $key => $value) {
					$this->relevanceResource[$key][$child_name] = $this->doGetRelevance($relevance_Obj, 'select', $relevance, $key);
				}
			}
		} else {
			 Tool::error_log("\$relevance_Obj = null ");
		}
	}*/

	/**
	 *  获取关联数据
	 *
	 * @param      $obj
	 * @param      $selectType
	 * @param      $relevance
	 * @param null $key
	 *
	 * @return mixed
	 */
	/*    function doGetRelevance(&$obj, $selectType, &$relevance, $key = null)
		{

			$relevance_table_key = $relevance['relevance_table_key'] ? $relevance['relevance_table_key'] : $obj->getPK();
			isset($relevance['where']) && $relevance['where'] && $where = $relevance['where'];
			if (is_null($key)) {
				$where = array($relevance_table_key => getArrVal($relevance['foreign_key'], $this->relevanceResource));

			} else {
				$where = array($relevance_table_key => getArrVal($relevance['foreign_key'], $this->relevanceResource[$key]));

			}

			$rs = $obj->field($relevance['field'])->$selectType($where);

			return $rs;
		}*/


	/*
	 * @param $dimension 数组维数
	 */
	/*  function relevance_Dispose($dimension = 1)
	  {

		  if (!$this->_relevance) {
			  return FALSE;
		  }
		  if (is_array($this->_relevanceArray)) {
			  foreach ($this->_relevanceArray as $child_name => $relevance) {
				  if (isset($relevance['type']) && isset($relevance['foreign_key'])) {
					  switch (strtolower($relevance['type'])) {
						  case 'manytoone':

							  $this->manyToOne($child_name, $relevance, $dimension);
							  break;
						  case 'onetoone':
							  $this->oneToOne($child_name, $relevance, $dimension);
							  break;

						  case 'manytomany':
							  break;
					  }
				  }
			  }
		  }
	  }*/

	/**
	 * 更新数据
	 *
	 * @param array  $data
	 * @param string $where
	 *
	 * @return bool|mixed
	 * @throws \PTLibrary\DB\DBException
	 */
	public function save( $data = array(), $where = '' ) {

			$this->setFieldData( $data );
			if ( $this->PKV ) {
				empty( $where ) && $where = array( $this->getPK() => $this->PKV );
				if ( $where && $this->FieldData ) {
					$this->VerifyRule();
					$res = $this->PDO->where( $where )->save( $this->FieldData );
					if ( $res !== false ) {
						$this->_entity->getContainer()->notify();
						$this->updateAfter( $this->FieldData );
					}

					return $res;
				}
			} else {

				$insert_id = $this->add( $this->FieldData );
				if ( $insert_id !== false ) {
					$this->addAfter( $insert_id );
				}

				return $insert_id;
			}

			return false;


	}

	/**
	 * 删除后操作
	 *
	 * @param  bool|int $result 删除结果
	 * @param null      $where  删除的条件
	 */
	public function deleteAfter( $result, $where = null ) {

	}

	/**
	 *  清空数据
	 */
	public function clearFieldData() {
		$this->FieldData = array();
	}

	/**
	 *  删除表的数据
	 *
	 * @param $where
	 *
	 * @return bool
	 */
	public function delete( $where = null ) {
		if ( $where ) {
			$result = $this->PDO->where( $where )->delete();
		} else {
			$result = $this->PDO->delete();
		}
		if(!$result){
			ThrowException::SystemException(ErrorHandler::DB_DELETE_FAIL);
		}
		$this->deleteAfter( $result, $where );
		return $result;
	}

	/**
	 *  通过主删除数据
	 *
	 *
	 * @param $pk_value
	 *
	 * @return bool|int
	 * @throws \Exception\DBException
	 */
	public function deleteByPK( $pk_value ) {
		if ( $pk_value ) {
			return $this->PDO->where( array( $this->PK => $pk_value ) )->delete();
		}

		return false;
	}


	/**
	 * 字段类型的自增
	 * $field 支持数组array('field'=>1,'field2'=>2)
	 *
	 * @param           $field
	 * @param int|float $step
	 *
	 * @return mixed
	 */
	public function setInc( $field, $step = 1 ) {
		$res=$this->PDO->setInc( $field, $step );
		if($res===false){
		    ThrowException::SystemException(ErrorHandler::DB_SET_INC_FAIL);
		}
		return $res;
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
	 * @param  string|array $field 字段
	 * @param int|float     $step  自增数值
	 *
	 * @return bool|mixed|string
	 * @throws \Exception\DBException
	 */
	public function setAutoInc( $field, $step = 1 ) {
		$res = $this->PDO->setAutoInc( $field, $step );
		if ( $res !== false ) {
			$this->_entity->getContainer()->notify();
		}

		return $res;
	}


	/**
	 * 自减
	 *
	 * $field 支持数组array('field'=>1,'field2'=>2)
	 *
	 * @param           $field
	 * @param int|float $step
	 *
	 * @return mixed
	 */
	public function setRnc( $field, $step = 1 ) {
		return $this->PDO->setRnc( $field, $step );
	}

	/**
	 * @param      $field
	 * @param null $msg
	 *
	 * @throws \Exception\FieldVerifyException
	 */
	public function FieldError( $field, $msg = null ) {
		if ( $field ) {
			$description = Tool::getArrVal( $field, $this->_FieldDescription, $field );
			$description .= ':' . $msg;
			FieldVerifyException::throwException( $description );
			//throw new \Exception($description, SystemError::PARAM_EXIT);
		}
	}

	/**
	 * 表单字段验证
	 *
	 *array(
	 * array('规则类型','验证字段(array())','验证规则'),
	 * )
	 *      规则类型：
	 *              Required 必填
	 *              Email 邮件
	 *              Mobile 手机号码
	 *              MaxLength 最大长度
	 *              MinLength 最小长度
	 *              Length 长度
	 *              Number 纯数字
	 *              Max 最大值
	 *              Min 最小值
	 *              String 字符串
	 *              unique 唯一的
	 *      验证规则：
	 *              可以是字符串，也可以是数组
	 *      验证字段：
	 *              array("字段1",""字段2,""字段3);
	 *
	 *
	 */
	public function VerifyRule() {
		if ( $this->DoVerifyRule && $this->_Rule ) {
			foreach ( $this->_Rule as $value ) {
				foreach ( $value[1] as $field ) {
					if ( $field ) {

						switch ( strtolower( $value[0] ) ) {
							case 'required':
								$description = '是必填项';
								if ( empty( $this->FieldData[ $field ] ) ) {
									$this->FieldError( $field, $description );
								}
								break;
							case 'email':
								$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
								if ( ! preg_match( $pattern, $this->FieldData[ $field ] ) ) {
									$this->FieldError( $field, '格式错误' );
								}
								break;
							case 'mobile':
								$pattern = "/1\d{10}/i";
								if ( ! preg_match( $pattern, $this->FieldData[ $field ] ) ) {
									$this->FieldError( $field, '格式错误' );
								}
								break;
							case 'maxlength':
								$maxValue = (int) ( $value[2] );
								if ( strlen( $this->FieldData[ $field ] ) > $maxValue ) {
									$this->FieldError( $field, '长度超过' . $maxValue );
								}
								break;
							case 'minlength':
								$minValue = (int) ( $value[2] );
								if ( strlen( $this->FieldData[ $field ] ) < $minValue ) {
									$this->FieldError( $field, '长度小于' . $minValue );
								}
								break;
							case 'length':
								$length = (int) ( $value[2] );
								if ( strlen( $this->FieldData[ $field ] ) != $length ) {
									$this->FieldError( $field, '长度必须等于' . $length );
								}
								break;
							case 'max':
								$length = (int) ( $value[2] );
								if ( strlen( $this->FieldData[ $field ] ) > $length ) {
									$this->FieldError( $field, '值不能大于' . $length );
								}
								break;
							case 'min':
								$length = (int) ( $value[2] );
								if ( strlen( $this->FieldData[ $field ] ) < $length ) {
									$this->FieldError( $field, '值不能小于' . $length );
								}
								break;
							case 'number':
								$pattern = "/\d{1,}/i";
								if ( ! preg_match( $pattern, $this->FieldData[ $field ] ) ) {
									$this->FieldError( $field, '必须是数字组成' );
								}
								break;
							case 'unique':
								$val = Tool::getArrVal( $field, $this->FieldData );
								if ( $val ) {
									$rs = $this->PDO->where( array( $field => $val ) )->find();
									if ( $rs ) {
										$this->FieldError( $field, '已经存在' );
									}
								}

								break;
						}
					} else {

					}
				}
			}
		}
	}

	/**
	 * 开启事务
	 */
	public function beginTransaction() {
		$this->PDO->beginTransaction();
	}

	/**
	 * @return bool
	 */
	public function commit() {
		$res = $this->PDO->commit();

		return $res;
	}

	/**
	 * 事务回滚
	 */
	public function rollback() {
		$this->PDO->rollback();
	}

	/**
	 * @param $sql
	 *
	 * @return \PDOStatement
	 */
	public function query( $sql ) {
		return $this->PDO->query( $sql );
	}

	/**
	 * 添加字段
	 *
	 * @param \DB\DbFiledParam $filedParam
	 *
	 * @return \PDOStatement
	 * @throws \Exception\DBException
	 */
	public function addField( DbFiledParam $filedParam ) {
		$filedParam->chkParam();
		if(isset($this->fieldArr[$filedParam->field])){
			ThrowException::DBException( 300210, '字段已存在' );
		}
		//重新检测表是否存在
		$this->PDO->table_exists($this->_table,true);
		$type = $this->getFiledTypeStr( $filedParam );

		$fullTable = $this->fullTableName;
		$this->quotes( $fullTable );
		$sql     = "alter table {$fullTable} add `{$filedParam->field}` {$type};";
		//Log::log($sql);
		return  $this->query( $sql );
	}

	/**
	 * @param \DB\DbFiledParam $filedParam
	 *
	 * @return string
	 * @throws \Exception\DBException
	 */
	private function getFiledTypeStr( DbFiledParam $filedParam ) {
		$filedParam->chkParam();
		$type    = $this->getFiledType( $filedParam );
		$isNull  = $this->getIsNull( $filedParam );
		$default = $this->getIsDefault( $filedParam );
		$comment = addslashes( $filedParam->comment );
		if( $filedParam->type == DbFiledParam::type_text ){
            $str = "{$type} COMMENT '{$comment}'";
        }else{
		    $str = "{$type} {$isNull} $default COMMENT '{$comment}'";
        }
		return $str;
	}

	public function getIsNull( DbFiledParam $filedParam ) {
		return $filedParam->is_null ? '' : 'not null';
	}

	/**
	 * 字段默认值
	 * @param \DB\DbFiledParam $filedParam
	 *
	 * @return string
	 */
	public function getIsDefault( DbFiledParam $filedParam ) {

		if ( in_array( $filedParam->type, [ DbFiledParam::type_float, DbFiledParam::type_double, DbFiledParam::type_decimal,DbFiledParam::type_int ] ) ) {
			return 'DEFAULT ' . ( is_null( $filedParam->default ) ? 'null' : $filedParam->default );
		} else {
			return 'DEFAULT ' . ( is_null( $filedParam->default ) ? 'null' : '\'' . addslashes($filedParam->default ). '\'' );
		}
	}

	/**
	 * 修改字段
	 *
	 * @param \DB\DbFiledParam $filedParam
	 *
	 * @return \PDOStatement
	 * @throws \Exception\DBException
	 */
	public function changeField(DbFiledParam $filedParam){
		$field = $filedParam->field;
		if(!$field){
			ThrowException::DBException( 300221, '字段名无效' );
		}
		$str = $this->getFiledTypeStr( $filedParam );
		$field = addslashes( $field );
		$newField = addslashes( $filedParam->new_field);
		$fullTable=$this->fullTableName;
		$this->quotes( $fullTable );
	    $sql="ALTER TABLE {$fullTable} CHANGE `{$field}` `{$newField}` {$str}";
		$changeRes = $this->query( $sql);
		if($changeRes){
			$this->PDO->getFields(true);
		}
		return $changeRes;
	}

	/**
	 * 对字符串加反单引号
	 * @param $str
	 */
	public function quotes(&$str) {
		if($str){
			$str='`'.str_replace('.','`.`',$str).'`';
		}
	}

	/**
	 * 获取字段类型
	 *
	 * @param \DB\DbFiledParam $filedParam
	 *
	 * @return string
	 */
	public function getFiledType( DbFiledParam $filedParam ) {
		$type = addslashes( $filedParam->type );
		$len = (int)$filedParam->length ;
		$point = ( $filedParam->point );
		if ( in_array( $filedParam->type, [ DbFiledParam::type_float, DbFiledParam::type_double, DbFiledParam::type_decimal ] ) ) {
			return "{$type}({$len},{$point})";
		} else if($filedParam->type == DbFiledParam::type_text){
			return DbFiledParam::type_text;
		}else{
			return "{$type}({$len})";
		}

	}

	/**
	 * 删除字段
	 *
	 * @param string $filed
	 *
	 * @return bool|\PDOStatement
	 * @throws \Exception\DBException
	 */
	public function delField( string  $filed ) {
		if(!$filed){
		    return false;
		}
		$filed = addslashes( $filed );
		if(!isset($this->fieldArr[$filed])){
			ThrowException::DBException( 300211, '字段不存在' );
		}
		$sql     = "alter table {$this->_table} drop $filed";
		if ( $res=$this->query( $sql ) ) {
			unset( $this->fieldArr[ $filed ] );

			return $res;
		}
		return false;
	}

	/**
	 * 设表前缀
	 *
	 * @param $prefix
	 *
	 * @return $this;
	 */
	public function setTablePrefix( $prefix ) {
		$this->_tablePrefix = $prefix;
		$this->PDO->setTablePrefix( $prefix );

		return $this;
	}

	/**
	 * 更新表
	 *
	 *  <pre>
	 *
	 * 表达式处理 expression
	 *  ['a'=>['expression'=>'a+3']]
	 *
	 * @VERSION 2.1
	 * @author  pantian
	 *
	 * @param      $data
	 * @param bool $isWhere true 必须限制条件 false 限制条件可有可无 注意 会修改全表
	 *
	 * @return bool|int
	 * @throws DBException
	 */
	public function update( $data, $isWhere = true ) {
		if ( $isWhere && ! $this->PDO->getWhere() ) {
			return false;
		}
		$res = $this->PDO->save( $data );
		if ( $res === false ) {
			ThrowException::SystemException(ErrorHandler::SAVE_FAIL);
		}else{
			$this->_entity->getContainer()->notify();
		}
		$this->updateAfter( $data );
		return $res;
	}

	/**
	 * 更新后操作
	 *
	 * @param $data
	 */
	public function updateAfter( $data ) {

	}

	public function getLastSql() {
		return $this->PDO->getLastSql();
	}

	public function getAllSql() {
		return $this->PDO->getAllSql();
	}

	/**
	 * 获取所有历史记录sql
	 *
	 * @return array
	 */
	public function getAllSqlHistory() {
		return $this->PDO->getSqlHistory();
	}

	/**
	 * 最后执行的数据
	 *
	 * @return array
	 */
	public function getLastExecuteData() {
		return $this->PDO->getLastExecuteData();
	}

	/**
	 * 创建索引
	 *
	 * @param \DB\DbIndexParam $dbIndexParam
	 *
	 * @return bool
	 * @throws \Exception\DBException
	 */
	public function createIndex(DbIndexParam $dbIndexParam){
		$db=$this->getDBName();
		$table=$this->getTableName();
		$dbIndexParam->chkParam();
		if($dbIndexParam->type==DbIndexParam::TYPE_PRIMARY_KEY){
			$dbIndexParam->indexName = '';
		}
		$param = ['table'=>$table,'type'=>$dbIndexParam->type,'filed'=>$dbIndexParam->filed,'indexName'=>$dbIndexParam->indexName];
		$sql="ALTER TABLE `{$db}`.`{$table}` ADD {$dbIndexParam->type} {$dbIndexParam->indexName} ({$dbIndexParam->filed}) comment '{$dbIndexParam->comment}'";
		//$sql="ALTER TABLE :table ADD :type :indexName (:filed)";
		//echo $sql,PHP_EOL;
		$this->PDO->prepare( $sql );
		return $this->PDO->execute( [] );
		//return $this->query( $sql );
	}

	/**
	 * 删除索引
	 *
	 * @param $indexName
	 *
	 * @return bool|\PDOStatement
	 * @return bool
	 */
	public function delIndex($indexName){
		if(!$indexName)return false;
		$db=$this->getDBName();
		$table=$this->_table;
		$sql="ALTER TABLE `{$db}`.`{$table}` DROP INDEX {$indexName}";
		$this->PDO->prepare( $sql );
		return $this->PDO->execute( [] );
	}

	/**
	 * 删除主键
	 *
	 * @return bool
	 */
	public function delPrimaryKey(){
		$db=$this->getDBName();
		$table=$this->_table ;
		$sql="ALTER TABLE `{$db}`.`{$table}` DROP INDEX PRIMARY KEY";
		$this->PDO->prepare( $sql );
		return $this->PDO->execute( [] );
	}


}

