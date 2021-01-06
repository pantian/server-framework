<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015-10-20
 * Time: 17:01
 */

namespace DB\Mongodb;
use Log\Log;
use Tool\Tool;

/**
 * 数据库链接
 * Class MongodbConnect
 *
 * @package Core\Lib\Mongodb
 */
class MongodbConnect
{

    static private  $instance=null;
    static private $connect;
    private function __construct()
    {

    }

    /**
     * 获取mongodb
	 * @param bool $reconnect
	 *
	 * @return \MongoDB\Driver\Manager
	 */
    static function getConnect($reconnect=false)
    {
        if(is_null(self::$connect) || $reconnect){
            self::$connect=self::connect();
        }

        return self::$connect;
    }

	/**
	 * @return \MongoDB\Driver\Manager
	 * @throws \Exception
	 */
    static private function connect()
    {
        try{

            $Config = \Yaconf::get( 'mongodb.'.CONF_KEY );
            $host = Tool::getArrVal( 'host' , $Config,'localhost' );
            $port = Tool::getArrVal( 'port' , $Config ,'port');
            $username = Tool::getArrVal('user',$Config);
            $password = Tool::getArrVal('password',$Config);
            $dbname = Tool::getArrVal( 'dbname' , $Config,'admin' );
            $connect_str = 'mongodb://';
            if($username && $password){
                $connect_str.= "$username:$password@";
            }
            $connect_str.="$host:$port";
            if($dbname){
                $connect_str.='/'. $dbname;
            }

	        $start = Tool::microtime_float();
            $connect = new \MongoDB\Driver\Manager( $connect_str);
	        Log::log( 'mongodb 链接ok,用时： ' . (Tool::microtime_float()-$start) );
            return $connect;
        }catch(\Exception $e){
            Log::error( 'mongodb 链接失败'.$e->getMessage());
            throw new \Exception( 'mongodb 链接失败' );
        }
    }

}