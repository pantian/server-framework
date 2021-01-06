<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/23
 * Time: 22:48
 */

namespace PTLibrary\Factory;



abstract class EntityFactoryBase {

	/**
	 * 获取实体类的单例实例
	 *
	 * @param $class
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public static function instance( $class ) {
		if ( ! class_exists( $class ) ) {
			throw new \Exception($class . '类不存在' ,300012);
		}
		$cloneInstance = InstanceFactory::cloneInstance( $class );
		if ( method_exists( $cloneInstance, 'init' ) ) {
			$cloneInstance->init();
		}

		return $cloneInstance;
	}

	/**
	 * @param $className
	 * @param $obj
	 */
	public static function initEntity($className,$obj){
		$proper=get_object_vars($className);

		foreach ( $proper as $key => $value ) {
			if( $key[0] != '_'){
				$obj->$key=$value;
			}
		}
	}
}