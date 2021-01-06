<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/28 0028
 * Time: 11:06
 */

namespace PTLibrary\Encrypt;

/**
 * 加密类
 * Class Encrypt
 *
 * @package Encrypt
 */
class Encrypt
{

    static $key = '#(*(SDF:ASDH-(+_+_U_Unadnasbas&**SD0871283';

    /**
     * 加密解密字符串
     * 使用方法:
     * 加密     :encrypt('str','E','nowamagic');
     * 解密     :encrypt('被加密过的字符串','D','nowamagic');
     *
     * @param string $string    需要加密解密的字符串
     * @param string $operation 判断是加密还是解密:E:加密   D:解密
     * @param string $key       加密的钥匙(密匙);
     *
     * @return mixed|string
     */
    public static function Encrypt( $string, $operation, $key = '' ) {
        $key           = md5( $key );
        $key_length    = strlen( $key );
        $string        = $operation == 'D' ? base64_decode( $string ) : substr( md5( $string . $key ), 0, 8 ) . $string;
        $string_length = strlen( $string );
        $rndkey        = $box = array();
        $result        = '';
        for ( $i = 0; $i <= 255; $i ++ ) {
            $rndkey[ $i ] = ord( $key[ $i % $key_length ] );
            $box[ $i ]    = $i;
        }
        for ( $j = $i = 0; $i < 256; $i ++ ) {
            $j         = ( $j + $box[ $i ] + $rndkey[ $i ] ) % 256;
            $tmp       = $box[ $i ];
            $box[ $i ] = $box[ $j ];
            $box[ $j ] = $tmp;
        }
        for ( $a = $j = $i = 0; $i < $string_length; $i ++ ) {
            $a         = ( $a + 1 ) % 256;
            $j         = ( $j + $box[ $a ] ) % 256;
            $tmp       = $box[ $a ];
            $box[ $a ] = $box[ $j ];
            $box[ $j ] = $tmp;
            $result .= chr( ord( $string[ $i ] ) ^ ( $box[ ( $box[ $a ] + $box[ $j ] ) % 256 ] ) );
        }
        if ( $operation == 'D' ) {
            if ( substr( $result, 0, 8 ) == substr( md5( substr( $result, 8 ) . $key ), 0, 8 ) ) {
                return substr( $result, 8 );
            } else {
                return '';
            }
        } else {
            return str_replace( '=', '', base64_encode( $result ) );
        }
    }


    /**
     * 对字符串加密处理
     *
     * @param string $str 加密的字符串
     *
     * @return bool|mixed|string
     */
    public static function PTEncrypt( $str ) {
        if ( $str && is_string( $str ) ) {

            return self::Encrypt( $str, 'E', self::$key );
        }

        return false;
    }

    /**
     * 对 PTEncrypt加密的字符串进行解密
     *
     * @param string $str 已加密的字符串
     *
     * @return bool
     */
    public static function PTDecrypt( $str ) {
        if ( $str && is_string( $str ) ) {

            return self::Encrypt( $str, 'D', self::$key);
        }

        return false;
    }

}