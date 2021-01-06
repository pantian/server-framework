<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/8/2
 * Time: 上午11:09
 */

namespace PTLibrary\Exception;

/**
 * 数据表实体数据校验异常
 * Class EntityVerifyException
 *
 * @package Exception
 */
class EntityVerifyException extends \Exception{
	/**
	 * EntityVerifyException constructor.
	 *
	 * @param string $code
	 * @param string $msg
	 */
	public function __construct($code,$msg='') {
		parent::__construct( $msg, $code );
	}
}