<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015-10-08
 * Time: 14:58
 */

namespace PTLibrary\DB;

use Bin\Cache\YacCache;
use Bin\DocParse\ClassDocInfo;
use Bin\Error\ErrorHandler;
use Bin\Exception\VerifyException;
use Bin\Exception\ThrowException;
use Bin\Log\Log;
use SplObserver;
use Bin\Tool\JSON;
use Bin\Tool\Tool;
use PTLibrary\Verify\MinVerify;
use PTLibrary\Verify\VerifyFactory;
use PTLibrary\Verify\VerifyRule;


/**
 * 基础实体容器
 * 容器：专门对数据表类的实体对象的管理，包括增，珊，改，查 等操作
 *
 * Class Container
 *
 * @package Core\Lib\Model\Container
 */
class MysqlContainer implements \SplSubject {
	/**
	 * 数据表实体队列
	 *
	 * @var array
	 */
	protected $_entityList = [];
	/**
	 * @var MysqlEntity
	 */
	protected $_entity = null;
	private static $instance = null;
	/**
	 * @var \SplObjectStorage
	 */
	private $observers = null;

	/**
	 * 操作成功数量
	 *
	 * @var int
	 */
	protected $successNumber = 0;
	/**
	 * @var BaseM
	 */
	protected $model = null;

	/**
	 * Container constructor.
	 *
	 * @param string $_id
	 */
	public function __construct( $_id = '' ) {
		$this->model     = new BaseM();
		$this->observers = new \SplObjectStorage();
	}

	function __clone() {
		foreach ( $this->observers as $observer ) {
			$this->observers->detach( $observer );
		}
	}


	/**
	 *
	 */
	function __destruct() {
		foreach ( $this->observers as $observer ) {
			$this->observers->detach( $observer );
			unset( $observer );
			Log::log( '删除观察对象' );
		}
	}

	/**
	 * 保存实体对象到数据库
	 * 参数Data 要保存的数据数组，并且要包含主键，主键成为保存的条件，如果为空，则是取实体数据
	 * @param array $data
	 *
	 * @return bool|int|mixed
	 * @throws \Bin\Exception\DBException
	 * @throws \Bin\Exception\FieldVerifyException
	 */
	function save(array $data=[]) {
		//获取实体属性数据
		$data || $data = $this->_entity->getProperty();
		if ( ! $data ) {
			return false;
		}
		if ( $this->_entity->isIsVerify() ) {
			$this->doVerify( $data );
		}
		$this->model->_entity = $this->_entity;
		$this->model->setDBName( $this->_entity->getDBName() );
		$this->model->setTable( $this->_entity->getTableName() );
		$this->pretreatmentData( $data );
		$res = $this->model->save( $data );
		if($res !==false)$this->notify();

		return $res;
	}

	/**
	 * @param $data
	 *
	 * @throws \Bin\Exception\VerifyException
	 */
	public function doVerify( &$data ) {
		$rule = $this->getVerifyRule();
		foreach ( $rule as $filed => $item ) {
			$verify = Tool::getArrVal( 'verify', $item );
			if ( $verify ) {
				if ( is_array( $verify ) ) {
					foreach ( $verify as $key => $_rule ) {
						if ( is_array( $_rule ) ) {
							$this->chkVerify( $filed, $_rule, $data, $item );
						} else {
							$this->chkVerify( $filed, $verify, $data, $item );
							break;
						}
					}
				}
			}
		}

	}

	/**
	 * 校验检测
	 * <pre>
	 * 单个：{"rule":"min","val":20,"error":"错误提示信息，没有则是默认"}
	 * 多点：[{"rule":"min","val":20,"error":"错误提示信息，没有则是默认"}]
	 * 对于email,mobile,required,number等规则，不用指val的值
	 * 规则说明：
	 * min 最小值
	 * max 最大值
	 * minLength 最小长度
	 * maxLength 最大长度
	 * required 必填
	 * number 数字类型
	 * numeric 数值类型
	 * email 邮箱格式
	 * mobile 邮箱格式
	 * unique 唯一限制
	 * pattern 正则规则
	 * between 数值大小在多少之间 {"rule":"between","val":20,50","error":"错误提示信息，没有则是默认"}
     * betweenLength 长度在多少之间 {"rule":"betweenLength","val":20,50",,"error":"错误提示信息，没有则是默认"}
     *
	 *</pre>
	 *
	 * @param string $field    字段名
	 * @param array  $rule     校验规则
	 * @param array  $data     被检验的数据
	 * @param array  $filedDoc 字体小注解doc
	 *
	 * @return bool
	 * @throws \Bin\Exception\VerifyException
	 */
	public function chkVerify( $field, $rule, &$data, $filedDoc ) {

		if ( ! $rule ) {
			throw new VerifyException( ErrorHandler::VERIFY_DATA_INVALID,
				ErrorHandler::getErrMsg( ErrorHandler::VERIFY_DATA_INVALID ) );
		}
		$ruleName = Tool::getArrVal( 'rule', $rule );
		$ruleVal  = Tool::getArrVal( 'val', $rule );//校验值
		$fieldDes = Tool::getArrVal( 'description', $filedDoc );//字段名描述与说明
		$errorMsg = Tool::getArrVal( 'error', $rule );
		$fieldDes || $fieldDes = $field;
		$filedVal = Tool::getArrVal( $field, $data );
		$this->chkVerifyDataType( $filedVal, $fieldDes );
		$verifyRule              = VerifyFactory::VerifyRule();
		$verifyRule->field       = $field;
		$verifyRule->description = $fieldDes;
		$verifyRule->value       = $filedVal;
		$verifyRule->error       = $errorMsg;
		$verifyRule->ruleType    = $ruleName;
		$verifyRule->ruleValue   = $ruleVal;

		$verifyRule->verify();
		return true;
	}

	/**
	 * 校验的字段数据类型
	 *
	 * @param $filedVal
	 * @param $filedDes
	 *
	 * @return bool
	 * @throws \Bin\Exception\VerifyException
	 */
	public function chkVerifyDataType( $filedVal, $filedDes ) {
		if ( is_string( $filedVal ) || is_numeric( $filedVal ) ) {
			return true;
		}
		throw new VerifyException( ErrorHandler::VERIFY_DATA_TYPE, $filedDes . '数据类型无效' );
	}


	/**
	 * 获取实体类的校验规则
	 *
	 * @return array
	 * @throws \Bin\Exception\VerifyException
	 */
	public function getVerifyRule() {
		//$cacheKey = md5( APP_PATH . $this->_entity->getDBName() . $this->_entity->getTableName() );
		try {
			$vL   = [];
			$list = ClassDocInfo::getPropertiesInfo( get_class( $this->_entity ) );
			foreach ( $list as $field => $item ) {
				$jsonStr = Tool::getArrVal( 'verify', $item );
				if ( $jsonStr ) {
					$item['verify'] = JSON::decode( $jsonStr );
					if ( ! $item['verify'] ) {
						throw new VerifyException( ErrorHandler::VERIFY_DATA_INVALID,
							Tool::getArrVal( 'description', $item ) . ErrorHandler::getErrMsg( ErrorHandler::VERIFY_DATA_INVALID ) );
					}
					$vL[ $field ] = $item;
				}
			}

			return $vL;
		} catch ( \Exception $exception ) {
			Log::error( $exception->getMessage() );
			throw new VerifyException( $exception->getCode(), $exception->getMessage() );
		}

	}

	/**
	 * 数据预处理，类型自动转换
	 *
	 * @param $data
	 */
	public function pretreatmentData( &$data ) {
		$field = ClassDocInfo::getFieldInfo( get_class( $this->_entity ) );
		foreach ( $data as $key => &$_d ) {

			$fieldInfo = Tool::getArrVal( $key, $field );
			if ( is_array( $_d ) ) {
				$this->pretreatmentData( $_d );
				continue;
			}
			$type = Tool::getArrVal( 'var', $fieldInfo );
			switch ( strtolower( $type ) ) {
				case 'int':
					$_d = (int) $_d;
					break;
				case 'bool':
					$_d = (bool) $_d;
					break;
				case 'float':
					$_d = (float) $_d;
					break;
				case 'string':
					$_d = (string) $_d;
					break;
				case 'array':
					if ( ! is_array( $_d ) ) {
						$_d = [];
					}
					break;
				case 'json':
					if ( ! is_array( $_d ) ) {
						$_d = [];
					}
					break;

			}
		}
	}

	function update() {
		//获取实体属性数据
		$data = $this->_entity->getProperty();
		$this->model->setDBName( $this->_entity->getDBName() );
		$this->model->setTable( $this->_entity->getTableName() );
		$res = $this->model->where( [ $this->getPK() => $this->getPKV() ] )->update( $data );
		if ( $res ) {
			$this->notify();
		}

		return $res;

	}

	/**
	 *
	 * 获取主键字段名
	 *
	 * @return string
	 */
	public function getPK() {
		return $this->getModel()->getPK();
	}

	/**
	 * 获取主键值
	 *
	 * @return mixed
	 */
	public function getPKV() {
		$pk  = $this->getModel()->getPK();
		$pkv = $this->_entity->$pk;

		return $pkv;
	}

	/**
	 * 删除数据
	 *
	 * @return bool
	 */
	function delete() {

		$res = $this->getModel()->delete( [ $this->getPK() => $this->getPKV() ] );
		if ( $res ) {
			$this->notify();
		}

		return $res;
	}

	/**
	 * 检测
	 */
	function checkDB() {
		$className = get_class( $this->_entity );
		if ( ! $this->_entity->getDBName() ) {
			ThrowException::DBException( ErrorHandler::MONGODB_DB_NAME_EMPTY );
		}
		if ( ! $this->_entity->getTableName() ) {
			//表名为空时，默认以实体类名做表名，除掉后缀'Entity'
			$className = substr( $className, strrpos( $className, '\\' ) + 1, strlen( $className ) );
			$this->_entity->setTableName( str_replace( 'Entity', '', $className ) );
		}
		if ( ! $this->_entity->getTableName() ) {
			ThrowException::DBException( ErrorHandler::DB_TABLE_EMPTY );
		}
		$this->model->setDBName( $this->_entity->getDBName() );
		$this->model->setTable( $this->_entity->getTableName() );
	}


	/**
	 * 获取所有数据
	 *
	 * @param array $where
	 *
	 * @return array
	 */
	function getAll( $where = [] ) {
		$res = $this->getModel()->setTable( $this->_entity->getTableName() )->where( $where )->select();

		return $res;
	}

	/**
	 * @param \Bin\DB\MysqlEntity $mysqlEntity
	 */
	function setEntity( MysqlEntity $mysqlEntity ) {
		$this->_entity = $mysqlEntity;
        $this->checkDB();
	}

	/**
	 * 获取模型
	 *
	 * @return \Bin\DB\BaseM|null
	 * @throws \Bin\Exception\DBException
	 */
	function getModel() {
		$this->model->setDBName( $this->_entity->getDBName() );
		$this->model->setTable( $this->_entity->getTableName() );
		$this->model->setEntity( $this->_entity );

		return $this->model;
	}

	/**
	 * @param \Bin\DB\MysqlEntity $mysqlEntity
	 *
	 * @return \Bin\DB\MysqlContainer|null
	 */
	static function instance( MysqlEntity $mysqlEntity ) {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		$clone = clone self::$instance;
		$clone->setEntity( $mysqlEntity );

		return $clone;
	}

	/**
	 * 按字段名添加数量
	 *
	 * @param  string $field 字段名
	 * @param int     $step  添加的数量
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function setInc( $field, $step = 1 ) {
		$res = $this->getModel()->where( [ $this->getPK() => $this->getPKV() ] )->setInc( $field, $step );
		if ( $res ) {
			$this->notify();
		}

		return $res;
	}

	/**
	 * 按字段名减少数量
	 *
	 * @param string $field 字段名
	 * @param int    $step  减少的数量
	 *
	 * @return bool
	 */
	public function setDnc( $field, $step = 1 ) {
		$res = $this->getModel()->where( [ $this->getPK() => $this->getPKV() ] )->setInc( $field, $step );
		if ( $res ) {
			$this->notify();
		}

		return $res;
	}

	/**
	 * 注册观察者
	 *
	 * @param \SplObserver $observer
	 */
	public function attach( SplObserver $observer ) {
		$this->observers->attach( $observer );
	}

	/**
	 * 注销观察者
	 *
	 * @param \SplObserver $observer
	 */
	public function detach( SplObserver $observer ) {
		$this->observers->detach( $observer );
	}

	/**
	 * 通知观察者
	 */
	public function notify() {
		foreach ( $this->observers as $observer ) {
			$observer->update( $this, $this->_entity );
		}
		$this->clearObserver();
	}

	/**
	 * 清除所有观察者对象
	 */
	public function clearObserver(){
		$this->observers->removeAll( $this->observers );
	}

}