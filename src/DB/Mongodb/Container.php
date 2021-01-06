<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015-10-08
 * Time: 14:58
 */

namespace DB\Mongodb;
use DocParse\ClassDocInfo;
use Error\ErrorHandler;
use Exception\ThrowException;
use Log\Log;
use Tool\Tool;


/**
 * 基础实体容器
 * 容器：专门对数据表类的实体对象的管理，包括增，珊，改，查 等操作
 *
 * Class Container
 *
 * @package Core\Lib\Model\Container
 */
class Container implements \SplSubject
{
    /**
     * 数据表实体队列
     *
     * @var array
     */
    protected $_entityList = [];
    /**
     * @var MongodbEntity
     */
    public      $_entity  = null;
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
     * @var MongoModel
     */
    protected $model = null;

	/**
	 * Container constructor.
	 *
	 * @param string $_id
	 */
    protected function __construct( $_id = '' )
    {
        $this->model = new MongoModel();
	    $this->observers = new \SplObjectStorage();
    }

	function __clone() {
		foreach ( $this->observers as $observer) {
			$this->observers->detach($observer);
    	}
	}


	/**
	 * 事件对象数组
	 * @var array
	 */
	function __destruct() {
		foreach ( $this->observers as $observer ) {
			$this->observers->detach($observer);
			unset( $observer );
			Log::log( '删除观察对象' );
		}
	}

    /**
     * 保存实体对象到数据库
     */
    function save()
    {
        //获取实体属性数据
        $data = $this->_entity->getProperty();
        if ( !$data ) {
            return false;
        }
        $this->model->setDBName( $this->_entity->getDBName() );
        $this->model->setTable( $this->_entity->getTableName() );
        $this->pretreatmentData( $data );
        $res = $this->model->save( $data );
	    $this->notify();
        return $res;
    }

    /**
     * 数据预处理，类型自动转换
     * @param $data
     */
    public function pretreatmentData( &$data )
    {
        $field = ClassDocInfo::getFieldInfo( get_class( $this->_entity ) );
        foreach ( $data as $key => &$_d ) {

            $fieldInfo = Tool::getArrVal( $key , $field );
            if(is_array($_d)) {
                $this->pretreatmentData( $_d );
                continue;
            }
            $type      = Tool::getArrVal( 'var' , $fieldInfo );
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
                    if(!is_array($_d)){
                        $_d = [];
                    }
                    break;
                case 'json':
                    if(!is_array($_d)){
                        $_d = [];
                    }
                    break;

            }
        }
    }

    function update()
    {
        //获取实体属性数据
        $data = $this->_entity->getProperty();
        $this->model->setDBName( $this->_entity->getDBName() );
        $this->model->setTable( $this->_entity->getTableName());
        $res = $this->model->where( ['_id' => $this->_entity->_id] )->update( $data );
		if($res){
			$this->notify();
		}
        return $res;
    }

    /**
     * 删除数据
     *
     * @return bool
     */
    function delete()
    {

        $res = $this->getModel()->delete( ['_id' => $this->_entity->_id] );
		if($res){
			$this->notify();
		}
        return $res;
    }

    /**
     * 检测
     */
    function checkDB()
    {
        $className = get_class( $this->_entity );
        if ( !$this->_entity->getDBName()) ThrowException::MongodbException( ErrorHandler::MONGODB_DB_NAME_EMPTY );
        if ( !$this->_entity->getTableName() ) {
            //表名为空时，默认以实体类名做表名，除掉后缀'Entity'
            $className                 = substr( $className , strrpos( $className , '\\' ) + 1 , strlen( $className ) );
	        $this->_entity->setTableName( str_replace( 'Entity', '', $className ) );
        }
        if ( !$this->_entity->getTableName()) ThrowException::MongodbException( ErrorHandler::MONGODB_DB_TABLE_EMPTY );
        $this->model->setDBName( $this->_entity->getDBName());
        $this->model->setTable( $this->_entity->getTableName() );
    }

    /**
     * 获取数据，并填充到实体对象中
     *
     * @param \DB\Mongodb\MongodbEntity|null $entity
     *
     * @return bool
     */
    function getData( MongodbEntity &$entity = null )
    {
        $entity && $this->addEntity( $entity );
        if ( $this->_entity->_id ) {
            $res = $this->getModel()->getById( $this->_entity->_id );
            if ( $this->_entity->setData( $res ) ) {
                return true;
            }
        }

        return false;

    }

    /**
     * 获取所有数据
     *
     * @param array $where
     *
     * @return array
     */
    function getAll( $where = [] )
    {
        $res = $this->getModel()->setTable( $this->_entity->getTableName() )->where( $where )->select();

        return $res;
    }

    function setEntity( MongodbEntity $entityBase )
    {
        $this->_entity = $entityBase;
        $this->checkDB();
    }

    /**
     * 获取模型
     *
     * @return \DB\Mongodb\MongoModel
     */
    function getModel()
    {
        $this->model->setDBName( $this->_entity->getDBName());
        $this->model->setTable( $this->_entity->getTableName());
	    $this->model->setEntity( $this->_entity );
        return $this->model;
    }

    /**
     *
     * @param \DB\Mongodb\MongodbEntity $entity
     *
     * @return \DB\Mongodb\Container|null
     */
    static function instance( MongodbEntity $entity )
    {

        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        $clone = clone self::$instance;
        $clone->setEntity( $entity );

        return $clone;
    }

    /**
     * 按字段名添加数量
     *
     * @param  string   $field 字段名
     * @param int $step  添加的数量
     *
     * @return bool
     * @throws \Exception
     */
    public function setInc( $field , $step = 1 )
    {
        $res=$this->getModel()->where( ['_id' => $this->_entity->_id] )->setInc( $field , $step );
        if($res){
	        $this->notify();
        }

	    return $res;
    }

	/**
	 * 按字段名减少数量
	 * @param string $field 字段名
	 * @param int $step  减少的数量
	 *
	 * @return bool
	 */
    public function setDnc( $field , $step = 1 )
    {
        $res=$this->getModel()->where( ['_id' => $this->_entity->_id] )->setDnc( $field , $step );
        if($res){
	        $this->notify();
        }

	    return $res;
    }

	/**
	 * 注册观察者
	 * @param \SplObserver $observer
	 */
	public function attach( \SplObserver $observer ) {
		$this->observers->attach( $observer );
	}

	/**
	 * 注销观察者
	 * @param \SplObserver $observer
	 */
	public function detach( \SplObserver $observer ) {
		$this->observers->detach( $observer );
	}

	/**
	 * 通知观察者
	 */
	public function notify() {
		foreach ( $this->observers as $observer ) {
			Log::log(get_class($observer));
			$observer->update( $this,$this->_entity );
    	}
	}
}