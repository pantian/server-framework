<?php


namespace PTLibrary\Session;


use PTFramework\Config;
use PTLibrary\Factory\InstanceFactory;
use PTLibrary\Tool\Context;
use PTLibrary\Tool\Request;
use PTLibrary\Tool\Tool;
use Simps\DB\Redis;

class Session {
	private $session_id = '';

	private $data = [];

	private $sessionTimeOut = 86400;

	private $session_key = '';

	/**
	 * @return self
	 */
	public static function instance() {
		$instance = Context::get( 'session' );
		if ( ! $instance ) {
			$instance                 = InstanceFactory::cloneInstance( self::class );
			$appConfig                = Config::getInstance()->get( 'application' );
			$instance->session_key    = trim( Tool::getArrVal( 'session_key', $appConfig ) );
			$instance->sessionTimeout = trim( Tool::getArrVal( 'session_timeout', $appConfig ) );

			Context::set( 'session', $instance );
		}

		return $instance;
	}

	public function start( $session_id = null ) {
		if ( is_null( $session_id ) ) {
			$this->sessionId();
		}
	}

	public function __destruct() {
	}

	/**
	 * 会话ID，设置会话ID或生成ID
	 *
	 * @param null $session_id
	 *
	 * @return bool|string|null
	 */
	public function sessionId( $session_id = null ) {
		if ( $session_id ) {
			$this->session_id = $session_id;

			return true;
		} else {
			if ( ! $this->session_id ) {
				$request=Request::instance();
				$this->session_id=$request->getCookie($this->session_key);
				$this->session_id || $this->session_id = $request->get( $this->session_key );
				$this->session_id || $this->session_id = $this->createSessionId();
			}

			return $this->session_id;
		}
	}

	public function createSessionId() {
		return Tool::getRandChar( 12 );
	}

	/**
	 *
	 * @param      $key
	 * @param null $value
	 *
	 * @return mixed|bool|null
	 */
	public function session( $key, $value = null ) {

		$contentStr = \PTLibrary\Cache\Redis::get( $key );

		if ( $contentStr ) {
			$this->data = unserialize( $contentStr );
		}
		if ( $key ) {
			if ( is_null( $value ) ) {
				return $this->data[ $key ] ?? null;
			} else {
				$key                = (string) $key;
				$this->data[ $key ] = $value;

				return \PTLibrary\Cache\Redis::set( $key, serialize( $this->data ), (int) $this->sessionTimeOut );
			}
		}

		return false;
	}
}