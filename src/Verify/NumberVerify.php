<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/14 0014
 * Time: 12:16
 */

namespace PTLibrary\Verify;

use Bin\Error\ErrorHandler;
use Bin\Exception\VerifyException;

/**
 * 纯数字校验类
 * Class NumberVerify
 *
 * @package Verify
 */
class NumberVerify implements Verify {
	/**
	 * @param \PTLibrary\Verify\VerifyRule $verifyRule
	 *
	 * @return bool
	 * @throws \Bin\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		if (  preg_match( "/^\d+$/", $verifyRule->value) == 0) {
			$verifyRule->error || $verifyRule->error= $verifyRule->getDes(). '不是有效的数字' ;
			throw new VerifyException( ErrorHandler::VERIFY_NUMBER, $verifyRule->error );
		}

		return true;
	}

}