<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/28 0028
 * Time: 10:37
 */

namespace PTLibrary\Exception;
use PTLibrary\Error\ErrorHandler;
use PTLibrary\Log\Log;

/**
 * 快速抛出异常
 * Class ThrowException
 *
 * @package Exception
 */
class ThrowException
{
    /**
     * 抛出数据库异常
     * @param        $code
     * @param string $msg
     *
     * @throws \PTLibrary\Exception\DBException
     */
    public static function DBException( $code , $msg ='')
    {
        throw new DBException( $code , $msg );
    }



	/**
	 * 抛出签名异常
	 * @param        $code
	 * @param string $msg
	 *
	 * @throws \PTLibrary\Exception\SignException
	 */
	public static function SignException( $code, $msg = '' ) {
		$msg || $msg = ErrorHandler::getErrMsg( $code );
		throw new SignException( $code, $msg );
	}

    /**
     * 信息异常
     * @param        $code
     * @param string $msg
     *
     * @throws \Exception\MessageException
     */
    public static function MessageException( $code, $msg = '' ) {
            $msg || $msg = ErrorHandler::getErrMsg( $code );
            throw new MessageException( $code, $msg );
        }

    /**
     * 抛出服务提供异常
     * @param        $code
     * @param string $msg
     *
     * @throws \Exception\ProvideException
     */
    public static function ProvideException($code,$msg='')
    {
        throw new ProvideException( $code , $msg );
    }

    /**
     * 抛出系统异常
     * @param        $code
     * @param string $msg
     *
     * @throws \Exception\SystemException
     */
    public static function SystemException( $code , $msg = '' )
    {
        $msg || $msg = ErrorHandler::getErrMsg( $code );
        Log::error( '系统异常:msg=' . $msg . ' ; code=' . $code );
        throw new SystemException( $code , $msg );
    }

    /**
     * mongodb 异常
     * @param $code
     * @param $msg
     *
     * @throws \Exception\MongodbException
     */
    public static function MongodbException( $code , $msg ='')
    {
        $msg || $msg = ErrorHandler::getErrMsg( $code );
        Log::error( 'mongodb异常:msg=' . $msg . ' ; code=' . $code );
        throw new MongodbException( $code , $msg );
    }

	/**
	 * 严重错误并通知
	 * @param        $code
	 * @param string $msg
	 *
	 * @throws \Exception\GrossErrorException
	 */
	public static function GrossErrorException($code,$msg=''){
		throw new GrossErrorException( $code , $msg );
	}

	/**
	 * 来访限制抛出
	 * @param        $code
	 * @param string $msg
	 *
	 * @throws \Exception\ComeException
	 */
	public static function ComeErrorException( $code, $msg='' ){
		Log::error( '严重错误:msg=' . $msg . ' ; code=' . $code );
		throw new ComeException( $msg, $code  );
	}
}