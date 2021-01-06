<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015-10-08
 * Time: 13:51
 */

namespace DB\Mongodb;


use DB\Entity;
use Tool\Tool;

class MongodbEntity extends Entity
{


    public $_id = '';

    protected function __construct( $_id = null )
    {
        $this->_container = Container::instance( $this );
        if ( $_id ) {
            $this->_id = $_id;
            $this->get();
        }
	    parent::__construct();
    }

	function getProperty( $key = null , $default = null )
	{
		if ( is_string( $key ) ) {
			if ( isset($this->$key) ) return [$key => $this->$key];
		}
		elseif ( is_array( $key ) ) {
			return $this->getPropertyByArray( $key , $default );
		}
		elseif ( is_null( $key ) ) {
			$arr = get_class_vars( get_class( $this ) );
			if($arr){
				foreach ( $arr as $key=>$item ) {
					if($key !='_id' && substr($key,0,1)=='_'){
						unset($arr[$key]);
					}
				}
			}
			return $this->getPropertyByArray( $arr , $default );
		}

		return false;
	}
	/**
	 * @return array
	 */
	function getDataToArr() {
		return $this->getPropertyByArray( $this->getProperty() );
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
								$where[$foreign_field]=['$in'=>$where_in_array];

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


}