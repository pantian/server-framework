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
 * 操作失败
 * 通过异常方式停止向下运行
 * Class DBException
 *
 * @package Exception
 */
class ActionErrorException extends \Exception
{
    const code = 10010;
    /**
     * 跳转url
     * @var string
     */
    protected $url = '';
    /**
     * 跳转时间
     * @var int
     */
    protected $time = 0;
    public function __construct( $message=null ) {

        parent::__construct( $message , self::code );

    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl( $url )
    {
        $this->url = $url;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime( $time )
    {
        $this->time = $time;
    }

    /**
     *
     * @param      $message
     * @param null $url
     * @param int  $time
     *
     * @throws \Exception\ActionErrorException
     */
    static function error( $message , $url=null , $time=0 )
    {
        $instance = new self( $message );
        $instance->setTime( $time );
        $instance->setUrl( $url );
        throw $instance;
    }
}