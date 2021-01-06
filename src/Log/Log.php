<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/22 0022
 * Time: 12:28
 */

namespace PTLibrary\Log;


use PTFramework\Config;
use Swoole\Table\Row;

class Log {

	private static $logPath;

	/**
	 * 日志信息
	 * @param $msg
	 * @param string $level
	 */
	public static function log($msg) {

		self::setPath();

		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}
		if(class_exists('Seaslog')){

			\SeasLog::info(PHP_EOL.$msg);
		}
		unset($msg);
	}


	private static function setPath(){
		if ( ! self::$logPath ) {
			$serverConfig=Config::getInstance()->getServersConfig();
			$path=$serverConfig['app_log_path']??BASE_PATH.'/logs/';
			self::$logPath=$path;
			if(class_exists('Seaslog')){
				\SeasLog::setBasePath( self::$logPath );

			}else{
				print_r('Seaslog extends is not fund');
			}
		}
	}


	/**
	 * 错误日志
	 *
	 * @param     $msg
	 * @param int $code
	 */
	public static function error($msg, $code = 0,$is_trace=false) {

		self::setPath();
		if (!is_string($msg)) {
			$msg = print_r($msg, true);
		}

		if(class_exists('Seaslog')){
		    \SeasLog::error(PHP_EOL.$msg.'        code= '.$code .PHP_EOL.($is_trace?self::getTrace():''));
		}

	}

	/**
	 * 严重错误日志，并发异常通知
	 *
	 * @param     $msg
	 * @param int $code
	 */
	public static function grossError($msg,$code=0){
	    self::error($msg,$code);
	}

	public static function getTrace() {
		$traceArr = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$traceContent = '';
		foreach ($traceArr as $key => $arr) {
			$argsVal = '';
			if (!empty($arr['args'])) {
				$argsVal = implode(',', $arr['args']);
			}
			if (!empty($arr['type'])) {
				$clf = $arr['class'] . $arr['type'] . $arr['function'];
			} else {
				$clf = $arr['function'];
			}
			$clf .= '(' . $argsVal . ')';
			$file = '';
			if (!empty($arr['file'])) {
				$file = $arr['file'];
			}
			$line = '';
			if (!empty($arr['line'])) {
				$line = $arr['line'];
			}
			$traceContent .= "[$key] {$clf} {$file} {$line}\n";
		}

		return $traceContent;
	}

}