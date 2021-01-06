<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/8/2
 * Time: 上午11:09
 */

namespace PTLibrary\Exception;

/**
 * 系统异常
 *
 * @package Exception
 */
class SystemException extends \Exception{
	/**
	 * SignException constructor.
	 *
	 * @param string $code
	 * @param string $msg
	 */
	public function __construct($code,$msg='') {
		parent::__construct( $msg, $code );
	}
}