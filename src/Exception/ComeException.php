<?php
/**
 * Created by PhpStorm.
 * User: htpc
 * Date: 2018/11/12
 * Time: 16:47
 */

namespace PTLibrary\Exception;



class ComeException extends \Exception
{
	public function __construct( string $message = "", int $code = 0, \Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}