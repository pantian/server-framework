<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/8/2
 * Time: 上午11:09
 */

namespace PTLibrary\Exception;

use PTLibrary\Error\ErrorHandler;
use PTLibrary\Log\Log;

/**
 * 系统异常
 * 严重错误，发送微信通知
 *
 * @package Exception
 */
class GrossErrorException extends \Exception{
	/**
	 * SignException constructor.
	 *
	 * @param string $code
	 * @param string $msg
	 */
	public function __construct($code,$msg='') {
		$msg || $msg = ErrorHandler::getErrMsg( $code );
		Log::error( '严重错误:msg=' . $msg . ' ; code=' . $code );
		parent::__construct( $msg, $code );

	}
}