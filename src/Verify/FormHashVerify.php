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
use Bin\Hash\FormHash;
use Bin\Log\Log;
use Bin\Request\Request;

/**
 * 表单Hash校验
 * Class FormHashVerify
 *
 * @package Verify
 */
class FormHashVerify implements Verify {
	/**
	 * @param \PTLibrary\Verify\VerifyRule $verifyRule
	 *
	 * @return bool
	 * @throws \Bin\Exception\VerifyException
	 */
	public function doVerifyRule( VerifyRule $verifyRule ) {
		$verifyRule->chkDataType();
		$verifyRule->value=Request::get('_form_hash');
		//Log::log('---------------------'.$verifyRule);
		if(!FormHash::verifyHash($verifyRule->value,true)){
			$verifyRule->error || $verifyRule->error= $verifyRule->getDes(). '请求无效' ;
			throw new VerifyException( ErrorHandler::VERIFY_FORM_HASH, $verifyRule->error );
		}
		return true;
	}

}