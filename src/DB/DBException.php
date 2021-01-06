<?php


namespace PTLibrary\DB;


use Throwable;

class DBException extends \Exception {
	public function __construct( $message = "", $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}