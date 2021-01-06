<?php

declare(strict_types=1);
namespace PTLibrary\DB\Connect;



use RuntimeException;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;

class PDO
{
	protected $pools;

	/**
	 * @var array
	 */
	protected $config = [
		'host' => 'localhost',
		'port' => 3306,
		'database' => 'test',
		'username' => 'root',
		'password' => 'root',
		'charset' => 'utf8mb4',
		'unixSocket' => null,
		'options' => [],
		'size' => 64,
	];

	private static $instance;


	public $chkNumber;

	private function __construct(array $config)
	{

		if ( ! $this->chkNumber ) {
			$this->chkNumber=new \Swoole\Atomic();
		}
		if (empty($this->pools)) {


			$this->config = array_replace_recursive($this->config, $config);
			print_r( $this->config );
			$this->pools = new PDOPool(
				(new PDOConfig())
					->withHost($this->config['host'])
					->withPort($this->config['port'])
					->withUnixSocket($this->config['unixSocket'])
					->withDbName($this->config['database'])
					->withCharset($this->config['charset'])
					->withUsername($this->config['username'])
					->withPassword($this->config['password'])
					->withOptions($this->config['options']),
				$this->config['size']
			);
		}
	}

	public static function getInstance($config = null)
	{
		if (empty(self::$instance)) {
			if (empty($config)) {
				throw new RuntimeException('pdo config empty');
			}
			if (empty($config['size'])) {
				throw new RuntimeException('the size of database connection pools cannot be empty');
			}
			self::$instance = new static($config);
		}

		return self::$instance;
	}

	public function getConnection()
	{
		//echo 'get PDO connect'.PHP_EOL;
		$this->chkNumber->add( 1 );
		return $this->pools->get();
	}

	public function close($connection = null)
	{
		//echo 'close PDO connect'.PHP_EOL;
		$this->pools->put($connection);
		$this->chkNumber->add(-1);
	}
}
