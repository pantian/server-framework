<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015-10-08
 * Time: 13:51
 */

namespace PTLibrary\DB;


use Bin\DocParse\ClassDocInfo;
use Bin\Log\Log;
use Bin\Tool\JSON;
use Bin\Tool\Tool;

/**
 * 实体基类
 * Class Entity
 *
 * @package DB\Mongodb
 */
abstract class Entity
{
    const RELEVANCE_ONE_TO_ONE   = 'OneToOne';
    const RELEVANCE_ONE_TO_MORE  = 'OneToMore';
    const RELEVANCE_MORE_TO_MORE = 'MoreToMore';
    /**
     * 数据库名
     *
     * @var string
     */
    protected $_dbName = '';
    /**
     * 表名
     *
     * @var string
     */
    protected $_tableName = '';
    /**
     * 是否开启关联
     *
     * @var bool
     */
    protected $_relevance = false;
    /**
     * @var \DB\Mongodb\Container|null
     */
    protected $_container;
	/**
	 * 是否字段验证
	 * @var bool
	 */
    protected $_is_verify=true;

    protected function __construct(  )
    {

    }



    function __clone()
    {

    }

    public function __destruct() {
	    unset( $this->_container );
    }

	/**
     * 字段查询，获取数据，并赋给实体对象
     *
     * @param $field
     * @param $filed_value
     *
     * @return bool
     */
    public function getInfoBy( $field , $filed_value )
    {
        if ( $field ) {
            $info = $this->getContainer()->getModel()->where( [$field => $filed_value] )->getOne();
            if ( $info ) {
                $this->setData( $info );

                return true;
            }
        }

        return false;
    }

	/**
	 * @return bool
	 */
	public function isIsVerify() {
		return $this->_is_verify;
	}

	/**
	 * @param bool $is_verify
	 */
	public function setIsVerify( $is_verify ) {
		$this->_is_verify = $is_verify;
	}

	/**
	 * 设置表名
	 * @param $table
	 */
	public function setTableName( $table ) {
		$this->_tableName = $table;
	}

	/**
	 * 设置数据库名
	 * @param $dbName
	 */
	public function setDbName( $dbName ) {
		$this->_dbName = $dbName;
	}

    /**
     *
     * @param null $_id
     *
     * @return array|bool|int
     */
    function get( $_id = null )
    {
        if ( $_id ) $this->_id = $_id;

        return $this->_container->getData();
    }

    /**
     * 设置ID,并获取数据
     *
     * @param $id
     */
    function setId( $id )
    {
        $this->get( $id );
    }


    /**
     * 获取属性变量与值
     * 返回数组
     *
     * $key=null ,返回全部属性
     * $key=['key1','key1'...] 返回多个值
     *
     * @param null $key
     * @param null $default
     *
     * @return array|bool
     */
    abstract function getProperty( $key = null , $default = null );

    /**
     * 设置关联
     * @param bool $is_relevance
     *
     * @return $this
     */
    public function relevance( $is_relevance=true )
    {
        $this->_relevance = $is_relevance;
        $this->getContainer()->getModel()->setEntity( $this );
        return $this;
    }

    public function getTableName()
    {
        return $this->_tableName;
    }

    public function getDBName()
    {
        return $this->_dbName;
    }
    
	/**
	 * 关联查询处理
	 * @param $data
	 */
    public function doRelevance( &$data )
    {
        if ( $this->_relevance ) {
            $relevanceInstances = $this->createRelevanceInstance();

            if ( $relevanceInstances && $data && is_array( $data ) ) {
                foreach ( $relevanceInstances as $relevanceIInstanceData ) {

	                $relevanceName = Tool::getArrVal( 'relevance_name' , $relevanceIInstanceData );
	                if(!$relevanceName){
		               continue;
	                }
	                $relevanceEntityInstance = Tool::getArrVal( 'entity_instance' , $relevanceIInstanceData );
	                $mod=$relevanceEntityInstance->relevance()->getContainer()->getModel();
	                $relevanceType = Tool::getArrVal( 'relevance_type' , $relevanceIInstanceData );
	                if(!$relevanceType)continue;
	                $relevanceData = Tool::getArrVal( 'relevance' , $relevanceIInstanceData );

	                if($relevanceData && is_array($relevanceData)){
						//采用in 查询条件
		                $foreign_field = key( $relevanceData );
		                $field = current( $relevanceData );
		                $where = [];



		                switch ( $relevanceType ) {
			                case self::RELEVANCE_ONE_TO_ONE:
				                $where_in_array = [];
				                foreach ( $data as &$DataItem ) {
					                $_f_v= Tool::getArrVal($field,$DataItem);
					                if(!in_array($_f_v,$where_in_array)) $where_in_array[] = $_f_v;
				                }
				                $where[$foreign_field]=['in'=>$where_in_array];

				                $mod->setResIndexField( $foreign_field );
				                $findRes=$mod->where($where)->select();
				                if($findRes){
					                foreach ( $data as &$DataItem ) {
						                $_f_v= Tool::getArrVal($field,$DataItem);
						                $DataItem[ $relevanceName ] = Tool::getArrVal( $_f_v, $findRes );
				                    }

				                }
				                unset( $findRes , $where,$where_in_array);
				                break;
			                case self::RELEVANCE_ONE_TO_MORE||self::RELEVANCE_MORE_TO_MORE:
				                foreach ( $data as &$DataItem ) {
					                $_f_v= Tool::getArrVal($field,$DataItem);
					                $where[$foreign_field]=$_f_v;
					                $DataItem[$relevanceName] = $mod->where($where)->select();
				                }
				                break;
		                }


	                }else{
		                continue;
	                }
	                unset($relevanceEntityInstance );
                }
            }
        }
    }


    /**
     * 把实体的属性转成数组
     *
     * @param array $key_arr
     * @param null  $default
     *
     * @return array
     */
    protected function getPropertyByArray( array $key_arr , $default = null )
    {
        $varsData = [];
	    foreach ( $key_arr as $key => $val ) {
            if ( isset($this->$key) ) {
                $varsData[$key] = $this->$key;
            }
            else{
                $varsData[$key] = $default;
            }
        }
        return $varsData;
    }

    /**
     * 设置数据
     * 如果没有数据，则把_id也设为空
     *
     * @param array $data
     *
     * @return bool
     */
    function setData( $data )
    {
        if ( $data ) {
            $argData = $this->getProperty();
            foreach ( $data as $key => $val ) {
                if ( isset($argData[$key]) ) {
                    $this->$key = $val;
                }
            }

            return true;
        }
        else {
            $this->_id = '';
        }

        return false;
    }

    /**
     * 获取实体数据，返回数组
     *
     * @return array
     */
    abstract function getDataToArr();


    /**
     * 保存实体数据
     *
     * @return array|bool|int
     */
    function save()
    {
        return $this->_container->save();
    }

    /**
     * 更新
     *
     * @return bool
     */
    function update()
    {
        return $this->_container->update();
    }

    /**
     * 删除本实体
     *
     * @return array|bool|int
     */
    function delete()
    {
        return $this->_container->delete();
    }

    function getAll()
    {
        return $this->_container->getAll();
    }

    /**
     * 获取容器
     *
     * @return \DB\Mongodb\Container|null
     */
    function getContainer()
    {
        return $this->_container;
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
        return $this->_container->setInc( $field , $step );
    }

    /**
     * 按字段名减少数量
     *
     * @param  string  $field 字段名
     * @param int $step  减少的数量
     *
     * @return bool
     * @throws \Exception
     */
    public function setDnc( $field , $step = 1 )
    {
        return $this->_container->setDnc( $field , $step );
    }

    /**
     * 按条件查数量
     *
     * @param array $where
     *
     * @return mixed
     */
    public function count( $where = [] )
    {
        return $this->_container->count( $where );
    }


    /**
     * 生成关联实例
     * @return bool|null
     */
    public function createRelevanceInstance()
    {
        $relevanceList  = ClassDocInfo::getEntityFieldInfo( get_class( $this ) );

        try {
            foreach ( $relevanceList as $field => &$item ) {

                $jsonStr     = Tool::getArrVal( 'relevance' , $item );
                $entityClass = Tool::getArrVal( 'entity' , $item );

                if ( $entityClass && class_exists( $entityClass ) ) {
                    $instance = new $entityClass();
                    if ( $instance instanceof Entity ) {

                        if ( $jsonStr ) {
                            $item['entity_instance']          = $instance;
                            $item['relevance']       = JSON::decode( $jsonStr );
                            $field=substr($field,1);
                            $item['relevance_name']       = $field;

                        }else{
                            Log::warning( '关联关系条件为空:');
                        }
                    }else{
                        Log::warning( '关联 $instance is not instanceof EntityBase :' . $entityClass );
                    }
                }else{
                    Log::warning( '关联 class is not exits:' .$entityClass );
                }
            }
        }
        catch ( \Exception $exception ) {
            Log::error( $exception->getMessage() );
        }

        return $relevanceList;
    }

	function __get( $name ) {
		if(isset($this->$name)){
			return $this->$name;
		}

		return null;
	}


	function __toString() {
		return (string)json_encode($this->getDataToArr());
	}
}