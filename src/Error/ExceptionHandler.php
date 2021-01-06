<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/7/24
 * Time: 上午10:59
 */

namespace PTLibrary\Error;

use PTLibrary\Log\Log;

class ExceptionHandler {
	static function error_handler() {
		$params = func_get_args();
		Log::error($params[1]);
	}

}