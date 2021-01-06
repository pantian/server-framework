<?php
/**
 * Created by PhpStorm.
 * User: pantian
 * Date: 2014/12/27
 * Time: 0:26
 */

namespace Bin\DB\Connect;

use Bin\Error\ErrorHandler;
use Bin\Exception\DBException;
use Bin\Log\Log;
use Bin\Tool\Tool;

class PDOConnect {
	static $instance = null;
	/**
	 * 数据库名
	 *
	 * @var null
	 */
	private $dbName = null;
	/**
	 * 数据库密码
	 *
	 * @var null
	 */
	private $password = null;

	/**
	 * 数据库服务器地址
	 *
	 * @var null
	 */
	private $host = null;
	/**
	 * 数据库服务端口
	 *
	 * @var null
	 */
	private $port = null;
	/**
	 * 数据库用户名
	 *
	 * @var null
	 */
	private $user = null;
	/**
	 * 表前缀
	 *
	 * @var null
	 */
	private $prefix = null;
	/**
	 * 数据库信息
	 *
	 * @var array
	 */
	public $DBConfig = [];

	public $currentDbName = '';

	/**
	 * @var \Swoole\Coroutine\MySQL
	 */
	private $db = null;
	/**
	 * 开始链接时间
	 *
	 * @var int
	 */
	static $connect_start_time = 0;
	/**
	 * mysql会话超时时间
	 *
	 * @var int
	 */
	static $session_wait_timeout = 0;

	function __construct( $dbName = null ) {
		$dbName || $this->dbName = $dbName;
	}


	function connect( $host = null, $port = null, $user = null, $password = '', $dbName = '', $prefix = '', $timeout = 0 ) {
		if ( $this->db ) {
			return true;
		}
		if ( is_array( $host ) ) {
			$config   = $host;
			$host     = Tool::getArrVal( 'host', $config, 'localhost' );
			$user     = Tool::getArrVal( 'user', $config, 'root' );
			$port     = Tool::getArrVal( 'port', $config, '3306' );
			$password = Tool::getArrVal( 'password', $config, '123456' );
			$dbName   = Tool::getArrVal( 'db_name', $config, '' );
			$prefix   = Tool::getArrVal( 'prefix', $config );
			$timeout  = Tool::getArrVal( 'timeout', $config );
			$charset  = Tool::getArrVal( 'charset', $config );
			$charset || $charset = 'utf8mb4';
		}
		$this->prefix = $prefix;
		$dsn          = "mysql:host={$host};port={$port}";
		if ( $this->dbName ) {
			$dbName = $this->dbName;
		}
		if ( $dbName ) {
			$dsn .= ';dbname=' . $dbName;
		}
		try {
			if ( ! $password ) {
				Log::error( '数据库密码为空：' );
//				trigger_error( '数据库密码为空', E_USER_ERROR );
				throw new DBException( ErrorHandler::DB_PASSWORD_EMPTY ,'数据库密码无效');
			}
			Log::log( '数据库编码:' . 'SET NAMES ' . $charset );
			/*$this->db=new \Swoole\Coroutine\MySQL();
			$this->db->connect(
				[
					'host'     => $host,
					'port'     => $port,
					'user'     => $user,
					'password' => $password,
					'database' => $dbName
				]
			);*/

			$this->db                 = new \PDO( $dsn, $user, $password, array(
				\PDO::ATTR_PERSISTENT         => true,
				//\PDO::ATTR_CASE => \PDO::CASE_LOWER,
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '. $charset,
				\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true
			) );

			$this->currentDbName      = $dbName;
			self::$connect_start_time = time();
			//$this->db->exec( 'set global wait_timeout = 36000' );//没有root用户，会出没有权限的异常
			Log::log( '数据库链接　OK' );
			echo '数据库链接　OK',PHP_EOL;

			return true;
		} catch ( \Exception $e ) {
			Log::error( 'mysql 数据库链接失败：' . $e->getMessage() . ' .   链接 dsn=' . $dsn . PHP_EOL . '配置：' . print_r( $config, true ) );

			throw new DBException( ErrorHandler::DB_CONNECT_FAIL );
		}
	}

	/**
	 * 检测链接
	 */
	function chkConnect() {
		if ( self::$session_wait_timeout > 0 && ( time() - self::$connect_start_time ) > ( self::$session_wait_timeout ) ) {
			unset( $this->db );
			$this->connect();
		}
	}

	/**
	 * @return null|PDOConnect
	 */
	static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return null|\PDO
	 */
	function getPDO() {
		return $this->db;
	}

	/**
	 * @return null
	 */
	public function getDbName() {
		return $this->dbName;
	}

	/**
	 * @return null
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @param null $password
	 */
	public function setPassword( $password ) {
		$this->password = $password;
	}

	/**
	 * @return null
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @param null $host
	 */
	public function setHost( $host ) {
		$this->host = $host;
	}

	/**
	 * @return null
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * @return null
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return null
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @return null
	 */
	public function getDb() {
		return $this->db;
	}

	/**
	 * 设置数据对象
	 *
	 * @param $db
	 */
	public function setDb( $db ) {
		$this->db            = $db;
		$this->currentDbName = '';
	}
}