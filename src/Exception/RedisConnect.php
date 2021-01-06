<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 16/8/4
 * Time: 下午11:39
 */

namespace PTLibrary\Exception;


class RedisConnect extends \Exception{
	public function __construct( $message, $code  ) {
		parent::__construct( $message, $code );
	}

}