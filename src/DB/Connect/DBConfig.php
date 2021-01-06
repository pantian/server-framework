<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/6 0006
 * Time: 16:26
 */

namespace DB\Connect;
use Tool\Tool;

/**
 * 数据库配置信息
 * Class DBConfig
 *
 * @package PTPhp\DB\Connect
 */
class DBConfig
{
    /**
     * 配置数组key
     * @var string
     */
    protected $config_key='';
    /**
     * 所有配置数据
     * @var array
     */
    protected $allConfig = array();

    /**
     * 当前数据配置
     * @var array
     */
    protected $current_config = array();
    /**
     * 数据类型
     * @var string
     */
    protected $database_type = 'mysql';

    public function __construct() {

    }

    public function init(  )
    {
        $this->loadConfig();
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return $this->config_key;
    }

    /**
     * @return string
     */
    public function getDatabaseType()
    {
        return $this->database_type;
    }

    /**
     * @param string $database_type
     */
    public function setDatabaseType( $database_type )
    {
        $this->database_type = $database_type;
    }


    /**
     * @return array
     */
    public function getAllConfig()
    {
        return $this->allConfig;
    }

    /**
     * @param array $allConfig
     */
    public function setAllConfig( $allConfig )
    {
        $this->allConfig = $allConfig;
    }

    /**
     * 当前配置数据
     * @return array
     */
    public function getCurrentConfig()
    {
        return $this->current_config;
    }

    /**
     * 获取配置项数据
     * @param      $key
     * @param null $default
     *
     * @return bool|null
     */
    public function get( $key ,$default=null)
    {
        return trim(Tool::getArrVal( $key , $this->getAllConfig(),$default));
    }

    /**
     * 加载配置数据
     * @return bool
     */
    public function loadConfig()
    {
        $dbConfig = \Tool\Tool::getConfig( 'mysql' );
        if($dbConfig){
            $this->setAllConfig( $dbConfig );
        }else{
            trigger_error( 'database config invalid' );
        }
        return false;
    }

    /**
     * 获取数据库名
     * @return bool|null
     */
    public function getDBName()
    {
        return $this->get( 'db_name' );
    }

    /**
     * 获取主机配置
     * @return bool|null
     */
    public function getHost()
    {
        return $this->get( 'host' , 'localhost' );
    }

    /**
     * 获取密码
     * @return bool|null
     */
    public function getPassword()
    {
        return $this->get( 'password' );
    }

    /**
     * 端口
     * @return bool|null
     */
    public function getPort()
    {
        return $this->get( 'port',3306 );
    }

    /**
     * 获取数据库用户名
     * @return bool|null
     */
    public function getUser()
    {
        return $this->get( 'user' ,'root');
    }

    /**
     * 获取数据库的链接会话时间长
     * @return bool|null
     */
    public function getWaitTimeout()
    {
        return $this->get( 'wait_timeout' ,3800000);
    }

    /**
     * 获取表前缀
     * @return bool|null
     */
    public function getTablePrefix()
    {
        return $this->get( 'prefix' );
    }



}