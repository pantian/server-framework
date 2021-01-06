<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/13 0013
 * Time: 15:36
 */

namespace DB\Mongodb\Exception;


use Log\Log;

class MongodbException extends \Exception
{
    public function __construct( $message , $code , \Exception $previous ) {
        Log::error( $message.', Error_code=' . $previous->getCode() . '; messte =' . $previous->getMessage() );
        parent::__construct( $message , $code , $previous );
    }
}