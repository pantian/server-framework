<?php


namespace PTLibrary\Tool;


use PTFramework\Context;
use PTFramework\Factory\InstanceFactory;

class Request {

	/**
	 * @var \Swoole\Http\Request
	 */
	protected $swooleRequest;


	public function __construct(){

	}

	public function __destruct(){

	}
	public function getRemoteIp(){
	    return $this->swooleRequest->server['remote_addr']??'';
	}

	public function requestMethod(){
	    return $this->swooleRequest->server['request_method'];
	}

	public function getCookie(string $key){
	    return $this->swooleRequest->cookie[$key]??null;
	}

	/**
	 * 获取GET数据
	 * @param string $key
	 * @param null   $default
	 *
	 * @return mixed|null
	 */
	public function get(string $key,$default=null){
	    return $this->swooleRequest->get[$key]??$default;
	}

	/**
	 * 获取POST数据
	 * @param string $key
	 * @param null   $default
	 *
	 * @return mixed|null
	 */
	public function post(string $key,$default=null){
	    return $this->swooleRequest->post[$key]??$default;
	}
	public static function instance(\Swoole\Http\Request $request=null){
		$key = '__request';
		$instance=Context::get($key);
		if($instance){
			return $instance;
		}
		$instance=InstanceFactory::cloneInstance(self::class);
		if ( $instance instanceof self && $request ) {
			$instance->swooleRequest=$request;
		}
		Context::set( $key, $instance );
		return $instance;
	}



	/**
	 * @return \Swoole\Http\Request
	 */
	public function getSwooleRequest(){
		return $this->swooleRequest;
	}

}