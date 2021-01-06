<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/28 0028
 * Time: 9:25
 */

namespace PTLibrary\Exception;


use PTLibrary\Error\ErrorHandler;
use PTLibrary\Log\Log;

/**
 * 数据库异常
 * Class DBException
 *
 * @package Exception
 */
class DBException extends \Exception
{
    public function __construct( $code,$message=null ) {
        $message || $message = ErrorHandler::getErrMsg( $code );

        Log::error( 'code=' . $code . ';message=' . $message. $this->getTraceAsString());
        parent::__construct( $message , $code );
    }
}