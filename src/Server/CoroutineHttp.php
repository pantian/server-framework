<?php

namespace PTFramework\Server;

use App\Listens\PoolB;
use Co\Channel;
use PTFramework\Application;
use PTFramework\Config;
use PTLibrary\Log\Log;
use PTLibrary\Tool\Tool;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;
use Swoole\Coroutine\Http\Server;
use function Swoole\Coroutine\run;
use Swoole\Process;

use Swoole\Process\Manager;
use Swoole\Process\Pool;

class CoroutineHttp {

	static $channel;
	/**
	 * @var Server
	 */
	static $server;
	function __construct() {
		Log::setPath();
//        self::$channel=new Channel(5);
		self::createProcess();
//        self::createServerProcess();
	}


	/**
	 * @param $parentCid
	 * @return mixed
	 */
	public static function createCoroutineServer(){
		$serverCid=Coroutine::create(function () {
			Coroutine::defer(function () {
				Log::log('第一个服务协程结束'.Coroutine::getCid());

				self::$channel->push('-1',3);

				self::$server->shutdown();

			});

			Log::log('开启协程服务 cid='.Coroutine::getCid());

			self::createServer();
		});
		return $serverCid;
	}

	public static function createProcess() {
		$pid = getmypid();
		$config     = Config::getInstance()->get( 'servers' );
		$pidFile=Tool::getArrVal('http.settings.pid_file',$config);
		file_put_contents($pidFile,$pid);

		$processName=$config['processName'];

		if ($processName){
			cli_set_process_title($processName);
		}


		self::createServerProcess();

//		$process = new Process(function ($p)   {
//			Log::log('创建主进程：'.getmypid());
//
//			while (1){
////				self::createServerProcess();
//				sleep(1);
//			}
//		},true);
//
//
//		$process->start();

		if( Tool::getArrVal('http.settings.daemonize',$config)){
//			Process::daemon();
		}

//		Process::wait(true);

		//todo 先不使用进程方式，使用pm2方式管理进程

		return true;


		//pool 版本,还有问题

		$pid = getmypid();
		$config     = Config::getInstance()->get( 'servers' );
		$pidFile=Tool::getArrVal('http.settings.pid_file',$config);
		file_put_contents($pidFile,$pid);
		if( Tool::getArrVal('http.settings.daemonize',$config)){
			Process::daemon();
		}
		$processName=$config['processName'];
		Log::log('启动主进程');
		if ($processName){
			cli_set_process_title($processName);
		}

		//多进程管理模块,只能一个进程
		$pool = new Process\Pool( 1 ,SWOOLE_IPC_UNIXSOCK);
		//让每个OnWorkerStart回调都自动创建一个协程
		Log::log('启动worker进程');
		$pool->set( [ 'enable_coroutine' => true ,'log_file'=>'/www/api4.log'] );
		$pool->on( 'workerStart', function ( Pool $pool, $id ) use($config){

			Log::log('worker start id= '.$id.PHP_EOL);
			cli_set_process_title($config['processName'].'.worker');
			self::createServer();
		} );
		$pool->on("WorkerStop", function ($pool, $workerId) {
			Log::log("Worker#{$workerId} is stopped\n");
		});

		print_r('启动worker进程');

		$pool->start();

	}

	/**
	 * 创建httpServer服务进程
	 * @return void
	 */
	public static function createServerProcess(){
		$config     = Config::getInstance()->clean()->get( 'servers' );
//		$serverProcess = new Process(function ( Process $pro )  {
		Log::log('worker start pid= '.posix_getpid());

		run(function (){

			try{
				self::createServer();
			}catch(\Exception $e){
				Log::log($e);
			}catch(\TypeError $error){
				Log::log($error);
			}

		});


//		},true);

//		$serverProcess->set(['stdout'=>Tool::getArrVal('http.settings.log_file',$config)]);
//        Process::daemon();
//		$serverProcess->start();

//		Process::wait();
	}

	public static function createServer() {
		$config     = Config::getInstance()->clean()->get( 'servers' );
//		print_r($config);
		$httpConfig = $config['http'];
		self::$server= $server     = new Server( $httpConfig['ip'], $httpConfig['port'], $httpConfig['ssl'] );
		$GLOBALS['http_server'] = $server;

		$processName=$config['processName'];
		cli_set_process_title($processName);
		$server->set( $httpConfig['settings'] );
		date_default_timezone_set( $config['timezone_set']??'Asia/Shanghai' );
		$server->handle( '/websocket', function ( Request $request, Response $ws ) use ($server) {
			PoolB::websocket($request,$ws,$server);
		} );
		$server->handle( '/', function ( Request $request, Response $response ) use ($server) {
//			Coroutine::create(function ()use($request,$response,$server){

//			Log::log($request->server['path_info']);
			PoolB::request($request,$response,$server);
//			});

		} );

		PoolB::onWorkerStart();
		Application::echoSuccess("server istarted   :{$httpConfig['ip']}:{$httpConfig['port']}" . PHP_EOL);
		Log::log('server is start success');
		$server->start();


	}

}