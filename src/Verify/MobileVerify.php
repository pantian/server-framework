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
 * 手机号码验证
 * Class MobileVerify
 *
 * @package Verify
 */
class MobileVerify implements Verify {
	/**
	 * @param \PTLibrary\Verify\VerifyRule $verifyRule
	 *
	 * @return bool
	 * @throws \Bin\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		if (preg_match('/^1\d{10}$/', $verifyRule->value) == 0) {
			$verifyRule->error || $verifyRule->error= $verifyRule->getDes(). '必须是手机号码';
			throw new VerifyException( ErrorHandler::VERIFY_MOBILE, $verifyRule->error );
		}
		return true;
	}

}