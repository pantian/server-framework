<?php

declare( strict_types=1 );

namespace PTFramework\Server;

use App\Library\Cache\SystemRequestCacheSTable;
use PTFramework\Application;
use PTFramework\Config;
use PTFramework\Context;
use PTFramework\DB\PDO;
use PTFramework\Listener;
use PTFramework\Route;
use PTFramework\Server\Protocol\HTTP\SimpleRoute;
use PTFramework\Tool\Tool;
use PTLibrary\Tool\JSON;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Table;
use Swoole\WebSocket\Server as HttpServer;


class Http {
	protected $_server;

	protected $_config;

	protected $_server_name;

	/** @var \Simps\Route */
	protected $_route;
	/**
	 * table 缓存
	 * @var Table
	 */
	protected $RTableCacheInstance;

	public function __construct() {
		$config             = Config::getInstance()->get( 'servers' );
		$httpConfig         = $config['http'];
		$this->_config      = $httpConfig;
		$this->_server_name = $httpConfig['server_name'];
		$this->requestTableCache();
		$this->_server = new \Swoole\WebSocket\Server(
			$httpConfig['ip'],
			$httpConfig['port'],
			$config['mode'],
			$httpConfig['sock_type']
		);
		$this->_server->RTableCache=$this->RTableCacheInstance;
		//$this->requestTableCache=SystemRequestCacheSTable::getInstance();
		$this->_server->on( 'workerStart', [ $this, 'onWorkerStart' ] );
		$this->_server->on( 'request', [ $this, 'onRequest' ] );
		$this->_server->on( 'task', [ $this, 'onTask' ] );
		$this->_server->on( 'finish', [ $this, 'onFinish' ] );


		$this->_server->set( $httpConfig['settings'] );
		$this->_server->on( 'managerStart', [ $this, 'onManagerStart' ] );
		$this->_server->on( 'start', [ $this, 'onStart' ] );
		$this->_server->on( 'connect', function ( \Swoole\WebSocket\Server $server, $request ) {

		} );
		$this->_server->on( 'open', function ( \Swoole\WebSocket\Server $server, $request ) {
			//echo "server: websoceck open success with fd{$request->fd}\n";
			Listener::getInstance()->listen( 'open', $server, $request );
		} );
		$this->_server->on( 'message', function ( \Swoole\WebSocket\Server $server, $frame ) {
			//echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
			try {
				$request = new Request();
//				print_r( $frame );
				$dataJson = json_decode( $frame->data, true );
				//print_r( $dataJson );
				if ( $dataJson ) {
					$request->server['path_info']      = $dataJson['action'] ?? '';
					$request->server['request_uri']    = $dataJson['action'] ?? '';
					$request->server['request_method'] = 'GET';
					$request->header                   = [];
					$request->post                     = $dataJson['data'] ?? [];
					$request->get=&$request->post;

					$request->isWebsocket = 1;
					$request->isResponse  = $dataJson['isResponse'] ?? false;//是否给前端返回
					$request->wss_request_id=$dataJson['wss_request_id']??'';
					$response             = new Response();
					$response->server     = $server;
					$request->isWebsocket = $response->isWebsocket = 1;
					$request->fd          = $response->fd = $frame->fd;

					$this->_route->webSocketDispatch( $request, $response );

				}
			} catch ( Exception $e ) {
				echo 'server '. $e->getMessage() . PHP_EOL;
			}


			//$res = $this->_route->dispatch( $request, $response );


			//$server->push($frame->fd, "this is server");
		} );
		$this->_server->on( 'close', function ( \Swoole\WebSocket\Server $server, $fd ) {
			if ( $server->isEstablished( $fd ) ) {
				Listener::getInstance()->listen( 'wss_close', $server, $fd );
			}
		} );

		foreach ( $httpConfig['callbacks'] as $eventKey => $callbackItem ) {
			[ $class, $func ] = $callbackItem;
			$this->_server->on( $eventKey, [ $class, $func ] );
		}

		if ( isset( $this->_config['process'] ) && ! empty( $this->_config['process'] ) ) {
			foreach ( $this->_config['process'] as $processItem ) {
				[ $class, $func ] = $processItem;
				$this->_server->addProcess( $class::$func( $this->_server ) );
			}
		}

		try {
			$this->_server->start();
		} catch ( Exception $e ) {
			echo 'Error:' . $e->getMessage() . PHP_EOL;
		} catch ( \Error $e ) {
			echo 'Error:' . $e->getMessage() . PHP_EOL;
		}
	}

	/**
	 * table 缓存对象
	 * @return \Swoole\Table
	 */
	public function requestTableCache(){
	    if(!$this->RTableCacheInstance){
			$this->RTableCacheInstance =new Table(10);
			$this->RTableCacheInstance->column('number',Table::TYPE_INT);
			$this->RTableCacheInstance->create();
			$this->RTableCacheInstance->set('request',['number'=>0]);//系统请求数量统计
		}
		return $this->RTableCacheInstance;
	}

	public function onStart( HttpServer $server ) {

		swoole_set_process_name( $this->_server_name . '.main' );
		// Application::echoSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
		Listener::getInstance()->listen( 'start', $server );
	}

	public function onManagerStart( HttpServer $server ) {
		swoole_set_process_name( $this->_server_name . '.manager' );
		Application::echoSuccess( "Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}" );
		Listener::getInstance()->listen( 'managerStart', $server );
	}

	public function onTask( $server,  \Swoole\Server\Task $task ) {
//		print_r($task);
		Listener::getInstance()->listen( 'onTask', $server, $task->data );
		$task->finish('');
	}



	public function onFinish(  $server, $task_id,  $data ) {

	}

	public function onWorkerStart( HttpServer $server, int $workerId ) {
		$this->_route = Route::getInstance();
		if ( $server->taskworker ) {

			$processName = $this->_server_name . '.Task.' . $workerId;
		} else {

			$processName = $this->_server_name . '.worker.' . $workerId;
		}
		swoole_set_process_name( $processName );
		Application::echoSuccess( $processName );
		Listener::getInstance()->listen( 'workerStart', $server, $workerId );
	}

	public function onSimpleWorkerStart( HttpServer $server, int $workerId ) {
		$this->_route = SimpleRoute::getInstance();
		Listener::getInstance()->listen( 'simpleWorkerStart', $server, $workerId );
	}

	public function onRequest( \Swoole\Http\Request $request, \Swoole\Http\Response $response ) {
		//print_r($this->requestTableCache);
		//print_r($this->requestTableCache());
		//$request->requestTableCache=$this->requestTableCache();
		$res = $this->_route->dispatch( $request, $response );

	}

	public function onReceive( $server, $fd, $from_id, $data ) {
		$this->_route->dispatch( $server, $fd, $data );
	}
}
