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
 * 最大值校验类
 * Class MaxVerify
 *
 * @package Verify
 */
class MaxVerify implements Verify {
	/**
	 * @param \PTLibrary\Verify\VerifyRule $verifyRule
	 *
	 * @throws \Bin\Exception\VerifyException
	 * @return null
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		if ( floatval( $verifyRule->ruleValue ) < floatval( $verifyRule->value ) ) {
			$verifyRule->error || $verifyRule->error= $verifyRule->getDes(). '不能大于' . $verifyRule->value;
			throw new VerifyException( ErrorHandler::VERIFY_MAX, $verifyRule->error );
		}
	}

}