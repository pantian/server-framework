<?php


namespace PTLibrary\Cache;

/**
 * Redis缓存
 * Class Redis
 *
 * @package PTLibrary\Cache
 */
class Redis {


	public static function get($key){
		$redis=\Simps\DB\Redis::getInstance()->getConnection();
		$res = $redis->get( $key );
		$redis->close();
		return $res;
	}

	public static function set($key,$value,$timeout=null){
		$redis=\Simps\DB\Redis::getInstance()->getConnection();
		$res = $redis->set( $key ,$value ,$timeout);
		$redis->close();
		return $res;
	}

	public static function del($key){

		$redis=\Simps\DB\Redis::getInstance()->getConnection();
		$res = $redis->del( $key );
		$redis->close();
		return $res;
	}

}