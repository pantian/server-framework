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
 * 正则校验
 * Class PatternVerify
 *
 * @package Verify
 */
class PatternVerify implements Verify {
	/**
	 * @param \PTLibrary\Verify\VerifyRule $verifyRule
	 *
	 * @throws \Bin\Exception\VerifyException
	 * @return null
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		if ( $verifyRule->ruleValue && ! preg_match( $verifyRule->ruleValue, $verifyRule->value) ) {
			$verifyRule->error || $verifyRule->error = $verifyRule->getDes() . '匹配不通过';
			throw new VerifyException( ErrorHandler::VERIFY_PATTERN, $verifyRule->error );
		}
	}

}