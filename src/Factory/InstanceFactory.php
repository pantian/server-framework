<?php


namespace PTFramework\Factory;

/**
 * 类实例化工厂
 * Class InstanceFactory
 *
 * @package PTFramework\Factory
 */
class InstanceFactory {

	private static $instanceList=[];

	/**
	 * 类的单例工厂
	 *
	 * @param $class
	 *
	 * @return mixed
	 */
	public static function instance($class){
	    if(isset(self::$instanceList[$class])){
	        return self::$instanceList[$class];
	    }

		if(is_string($class) && class_exists($class)){
	        self::$instanceList[$class]=new $class();
	    }

		return self::$instanceList[ $class ];
	}

	/**
	 * 返回实例副本
	 * @param $class
	 *
	 * @return mixed
	 */
	public static function cloneInstance($class){
		$instance = self::instance( $class );
		return clone $instance;
	}

}