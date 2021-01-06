<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/26 0026
 * Time: 15:56
 */
namespace PTLibrary\Tool;

class JSON
{
	private function __construct() {
	}

	/**
     * 把数组转移成json字符串
     * @param array $arr
     * @param boolean $options
     *
     * @Version 1
     * @Author  pantian
     * @return bool|string
     */
    public static function encode (array $arr,$options=true){
        if(is_array($arr)){
            if(function_exists('json_encode')){
                return json_encode($arr, $options);
            }
        }

        return false;
    }

    /**
     * 解析 json
     * @param     $json
     * @param int $assoc
     *
     * @Version 1
     * @Author  pantian
     * @return bool|mixed
     */
    public static function decode ($json, $assoc=1){
        if(is_string($json)){
            if(function_exists('json_decode')){
                return json_decode($json, $assoc);
            }
        }

        return false;
    }
}