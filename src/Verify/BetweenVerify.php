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
 * 数值大小范围校验
 * Class BetweenVerify
 *
 * @package Verify
 */
class BetweenVerify implements Verify {
	/**
	 * @param \PTLibrary\Verify\VerifyRule $verifyRule
	 *
	 * @return bool
	 * @throws \Bin\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		$verifyRule->value = floatval( $verifyRule->value );
		list( $min, $max ) = explode( ',', $verifyRule->ruleValue );
		$verifyRule->minValue = $min;
		$verifyRule->maxValue = $max;
		if ( $verifyRule->value< (float) $verifyRule->minValue|| $verifyRule->value > (float) $verifyRule->maxValue) {
			$verifyRule->error || $verifyRule->error = $verifyRule->getDes(). '的大小必须在' . $verifyRule->minValue . '~' . $verifyRule->maxValue. '之间';
			throw new VerifyException( ErrorHandler::VERIFY_BETWEEN_VALUE,$verifyRule->error);
		}
		return true;
	}

}