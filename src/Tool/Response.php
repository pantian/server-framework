<?php


namespace PTLibrary\Tool;


use PTFramework\Context;
use PTFramework\Factory\InstanceFactory;

/**
 * 响应对象
 * Class Response
 *
 * @package PTFramework\Tool
 */
class Response {

	protected \Swoole\Http\Response $swooleResponse;

	public static function instance(\Swoole\Http\Response $response){
		$key = '__response';
		$instance=Context::get($key);
		if($instance){
			return $instance;
		}
		$instance=InstanceFactory::cloneInstance(self::class);
		if ( $instance instanceof self && $response ) {
			$instance->swooleResponse=$response;
		}
		Context::set( $key, $instance );
		return $instance;
	}

	/**
	 * @return \Swoole\Http\Response
	 */
	public function getSwooleResponse(){
	    return $this->swooleResponse;
	}
}