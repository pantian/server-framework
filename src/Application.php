<?php

declare(strict_types=1);

namespace PTFramework;

use PTFramework\Server\CoroutineHttp;
use PTLibrary\Log\Log;
use SebastianBergmann\CodeCoverage\Report\PHP;

class Application
{
    /**
     * @var string
     */
    protected static $version = '1.4';

    public static function welcome()
    {
        $appVersion = self::$version;
        $swooleVersion = SWOOLE_VERSION;
        echo" http server framework Version: {$appVersion}, Swoole: {$swooleVersion}".PHP_EOL.PHP_EOL;
    }

    public static function println($strings)
    {
        Log::log( $strings . PHP_EOL);
    }

    public static function echoSuccess($msg)
    {
        self::println('[' . date('Y-m-d H:i:s') . '] [INFO] ' . "\033[32m{$msg}\033[0m");
    }

    public static function echoError($msg)
    {
        self::println('[' . date('Y-m-d H:i:s') . '] [ERROR] ' . "\033[31m{$msg}\033[0m");
    }

	public static function killProcess($processName){
	    $mod="ps -ef|grep {$processName}|cut -c 9-15|xargs kill -9";

	    print_r(system($mod));


	}

    public static function stop(){

    }


    public static function run()
    {
        self::welcome();
        global $argv;
        $count = count($argv);
        $funcName = $argv[$count - 1];
        $command = explode(':', $funcName);
        switch ($command[0]) {
            case 'http':
//                $className = \PTFramework\Server\Http::class;
                $className = CoroutineHttp::class;
                break;
            case 'ws':
                $className = \PTFramework\Server\WebSocket::class;
                break;
            case 'mqtt':
                $className = \PTFramework\Server\MqttServer::class;
                break;
            case 'main':
                $className = \PTFramework\Server\MainServer::class;
                break;
            default:
                // 用户自定义server
                $configs = config('servers', []);
                if (isset($configs[$command[0]], $configs[$command[0]]['class_name'])) {
                    $className = $configs[$command[0]]['class_name'];
                } else {
                    exit(self::echoError("command {$command[0]} is not exist, you can use {$argv[0]} [http:start, ws:start, mqtt:start, main:start]"));
                }
        }
        switch ($command[1]) {
            case 'start':
                new $className();
                break;
            default:
                self::echoError("use {$argv[0]} [http:start, ws:start, mqtt:start, main:start]");
        }
    }

	
}
