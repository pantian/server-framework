<?php


namespace PTFramework\MiddlewareInterface;

use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * 请求中间件接口
 * Interface RequestMiddlewareInterface
 *
 * @package PTFramework
 */
interface RequestMiddlewareInterface {
public static function RequestStart( Request  $request,Response $response);
public static function end(Request  $request,Response $response);
}