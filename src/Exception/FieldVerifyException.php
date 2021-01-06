<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015-10-28
 * Time: 17:54
 */

namespace PTLibrary\Exception;
use PTLibrary\Error\ErrorHandler;


/**
 * 数据库字段验证异常处理
 *
 * Class FieldVerifyException
 *
 * @package PTPhp\Exception
 */
class FieldVerifyException extends \Exception
{
	function __construct($msg)
	{
		parent::__construct( $msg , ErrorHandler::DB_FIELD_VERIFY_EXCEPTION );
	}

	static function throwException($msg)
	{
		throw new self( $msg );
	}

}