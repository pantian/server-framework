<?php
/**
 * Created by PhpStorm.
 * User: pantian
 * Date: 2015/4/12
 * Time: 17:30
 */

namespace PTLibrary\Tool;

use Cache\RedisCache;
use Cache\SessionCache;
use Dir\Dir;
use Log\Log;
use Request\Request;
use Yaf\Dispatcher;

class Tool {
	const MSG_CODE = 33080;


	private function __construct() {
	}

	/**
	 * 加载文件
	 *文件不存在，返回 false
	 *
	 * @param      $file
	 * @param bool $is_once
	 *
	 * @return mixed
	 */
	public static function loadFile( $file, $is_once = false ) {
		if ( file_exists( $file ) ) {
			if ( $is_once ) {
				return include_once $file;
			}

			return include $file;
		} else {
			return false;
		}
	}



	/**
	 * 当前文件目录
	 *
	 * @return string
	 */
	public static function getDir() {
		return __DIR__;
	}

	/**
	 * 获取随机字符串
	 *
	 * @param $length
	 *
	 * @return null|string
	 */
	public static function getRandChar( $length, $not_number = false ) {
		$str    = null;
		$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";

		$not_number && $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		$length || $length = 4;
		$max = strlen( $strPol ) - 1;
		for ( $i = 0; $i < $length; $i ++ ) {
			$str .= $strPol[ rand( 0, $max ) ];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
		}

		return $str;
	}


	/**
	 * 文件缓存
	 *
	 * @param  string $name       key
	 * @param string  $val        value
	 * @param int     $expiration 过期时间（秒）
	 *
	 * @return bool|mixed
	 */
	public static function S( $name, $val = '', $expiration = 0 ) {
		$path         = self::getDataCachePath();
		$nameArr      = explode( '.', $name );
		$nameArrCount = count( $nameArr );
		$data = [];
		if ( strlen( $name ) == 0 ) {
			return false;
		}
		if ( is_null( $val ) ) {
			self::F( $name, null, $path );
		} elseif ( $val === '' ) {
			//如果 name 有 '.'人分隔符，则以第一个做文件名
			if ( $nameArrCount == 1 ) {
				$content[ $name ] = $val;
			} else {
				$name = $nameArr[0];
			}
			$data = self::F( $name, '', $path );
			if ( $data ) {
				$time = self::getArrVal( 'time', $data );
				if ( $time && $time < time() ) {
					self::F( $name, null, $path );

					return null;
				} else {
					return self::getArrVal( 'content.' . $name, $data );
				}
			} else {
				return null;
			}
		} else {

			if ( $nameArrCount == 1 ) {

				$data = self::F( $name, '', $path );
				if(!is_array($data)){
					$data = [];
				}
				if ( $expiration > 0 ) {
					$data['time'] = time() + $expiration;
				}
				$content[ $name ] = $val;
			} else {

				if ( $expiration > 0 ) {
					$data['time'] = time() + $expiration;
				}
				$name = $nameArr[0];//如果 name 有 '.'人分隔符，则以第一个做文件名
				$data = self::F( $name, '', $path );
				if(!is_array($data)){
					$data = [];
				}
				$data || $data = array();
				$content = self::getArrVal( 'content', $data );
				$content || $content = array();
				$_t =& $content;
				foreach ( $nameArr as $key => $value ) {
					if ( ! isset( $_t[ $value ] ) ) {
						$_t[ $value ] = array();
					}

					$_t =& $_t[ $value ];
				}
				$_t = $val;
			}
			$data['content'] = $content;

			return self::F( $name, $data, $path );
		}
	}


	/**
	 * 获取数组的key下的值
	 *
	 * @param  string $key
	 * @param  array  $arr
	 * @param null    $default
	 * @param bool    $not_empty 不允许为空，即为空时，返回默认值
	 *
	 * @return bool|null
	 */
	public static function getArrVal( $key, $arr, $default = null, $not_empty = false ) {
		if ( ! is_array( $arr ) ) {
			return null;
		}
		if ( is_array( $key ) ) {
			return null;
		}
		$key = (string) $key;
		//var_dump( $key );
		$index = strpos( $key, '.' );
		$last  = null;
		if ( $index == false ) {
			$arg = $key;
		} else {
			$arg  = substr( $key, 0, $index );
			$last = substr( $key, $index + 1, strlen( $key ) );
		}
		if ( isset( $arr[ $arg ] ) ) {
			if ( $last && is_array( $arr[ $arg ] ) ) {
				$val = self::getArrVal( $last, $arr[ $arg ], $default, $not_empty );
			} else {
				$val = $arr[ $arg ];
			}
			if ( is_string( $val ) && strlen( $val ) == 0 && $not_empty ) {
				return $default;
			}

			return $val;
		}

		return $default;
	}

	/**
	 * 检测是不是HTML文件
	 *
	 * @param $file
	 *
	 * @return string
	 */
	public static function chkIsHTMLFile( $file ) {
		if ( ! preg_match( '/.*?\.html/is', $file ) ) {
			$file .= '.html';
		}

		return $file;
	}

	/**
	 * 解析 a=a&b=b 字字符
	 *
	 * @param $queryStr
	 *
	 * @return array
	 */
	public static function parseQueryString( $queryStr ) {
		$returnArr = [];
		if ( $queryStr ) {
			$arr1 = explode( '&', $queryStr );
			if ( $arr1 ) {
				foreach ( $arr1 as $val ) {
					if ( $val ) {
						list( $key, $value ) = explode( '=', $val );
						if ( $key ) {
							$returnArr[ $key ] = $value;
						}
					}
				}
			}
		}

		return $returnArr;
	}


	/**
	 * 邮箱验证
	 *
	 * @param $mail
	 *
	 * @return bool
	 */
	public static function IS_EMAIL( $mail ) {
		$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
		if ( preg_match( $pattern, $mail ) ) {
			return true;
		}

		return false;
	}

	/**
	 * 判断手机格式是否正确
	 *
	 * @param $mobile
	 *
	 * @return bool
	 */
	public static function IS_MOBILE( $mobile ) {
		if ( preg_match( "/^1\d{10}$/", $mobile ) ) {
			return true;
		}

		return false;
	}


	/**
	 * 文件后缀
	 *
	 * @param $file
	 *
	 * @return bool|string
	 */
	public static function getFileExf( &$file ) {
		$indexN = strrchr( $file, '.' );
		$type   = false;
		if ( $indexN ) {
			$type = substr( $indexN, 1 );
		}

		return strtolower( $type );
	}

	/**
	 * 定义常量
	 *
	 * @param $key
	 * @param $value
	 */
	public static function define( $key, $value ) {
		if ( ! defined( $key ) ) {
			define( $key, $value );
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
			$_key = self::getEncrypt_key();

			return self::Encrypt( $str, 'E', $_key );
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
			$_key = self::getEncrypt_key();

			return self::Encrypt( $str, 'D', $_key );
		}

		return false;
	}

	/**
	 * 返回加密密钥,不能修改
	 *
	 * @return string
	 */
	public static function getEncrypt_key() {
		return '';
	}

	/**
	 * 函数作用:加密解密字符串
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
			$result    .= chr( ord( $string[ $i ] ) ^ ( $box[ ( $box[ $a ] + $box[ $j ] ) % 256 ] ) );
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
	 *  中文截取，支持gb2312,gbk,utf-8,big5
	 *
	 * @param string $str     要截取的字串
	 * @param string $length  截取长度
	 * @param int    $start   截取起始位置
	 * @param string $charset utf-8|gb2312|gbk|big5 编码
	 * @param bool   $suffix  是否加尾缀
	 *
	 * @return string
	 */
	public static function csubstr( $str, $length, $start = 0, $charset = "utf-8", $suffix = true ) {
		if ( ! $length ) {
			return $str;
		}
		if ( function_exists( "mb_substr" ) ) {

			if ( mb_strlen( $str, $charset ) <= $length ) {
				return $str;
			}

			$slice = mb_substr( $str, $start, $length, $charset );

		} else {
			$re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";

			$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";

			$re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";

			$re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";

			preg_match_all( $re[ $charset ], $str, $match );

			if ( count( $match[0] ) <= $length ) {
				return $str;
			}

			$slice = join( "", array_slice( $match[0], $start, $length ) );
		}

		if ( $suffix ) {
			return $slice . "…";
		}

		return $slice;
	}

	/**
	 * 通过ip获取城市名
	 * @param $ip
	 *
	 * @return bool|string
	 */
	public static function IpToCity($ip){
		if(!$ip)return false;

		$IPLocation=IpLocation::getInstance();
		$info=$IPLocation->getlocation($ip);
		$city=Tool::getArrVal('country',$info);
		return $city;

	}

	/**
	 * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
	 *
	 * @param string $name  缓存名称
	 * @param mixed  $value 缓存值
	 * @param string $path  缓存路径
	 *
	 * @return mixed
	 */
	public static function F( $name, $value = '', $path = null ) {
		static $_cache = array();
		$path || $path = self::getDataPath();
		\PTFramework\Tool\Dir::create( $path );
		$filename = $path . $name . '.php';
		if ( '' !== $value ) {
			if ( is_null( $value ) ) {
				// 删除缓存
				if ( false !== strpos( $name, '*' ) ) {
					return false;
				} else {
					unset( $_cache[ $name ] );

					return unlink( $filename );
				}
			} else {

				if ( file_put_contents( $filename, serialize( $value ) ) ) {
					$_cache[ $name ] = $value;

					return true;
				} else {
					return false;
				}

			}
		}
		// 获取缓存数据
		if ( isset( $_cache[ $name ] ) ) {
			return $_cache[ $name ];
		}
		if ( file_exists( $filename ) ) {
			if ( $value = unserialize( file_get_contents( $filename ) ) ) {
				$_cache[ $name ] = $value;
			} else {
				$value = false;
			}
		}

		return $value;
	}

	/**
	 * 数据路径
	 *
	 * @return string
	 */
	public static function getDataPath() {
		if(defined('DOCUMENT_ROOT')){
			$path = DOCUMENT_ROOT . '/Data/';
		}else{
			$path = dirname( dirname(__DIR__ ));
		}

		return $path;
	}


	/**
	 * 数组转url参数
	 * 并进行urlencode
	 *
	 * @param array $arr
	 *
	 * @return string
	 */
	public static function ArrToGetParam( $arr ) {
		return is_array( $arr ) ? http_build_query( $arr ) : '';
	}

	/**
	 * 数据转成标准cookie字符串
	 *
	 * @param array $array
	 *
	 * @return string
	 */
	public static function ArrayToCookieString( array $array ) {
		$str = '';
		if ( $array && is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				$str .= "{$key}={$value};";
			}
		}

		return $str;
	}

	/**
	 * http get请求
	 *
	 * @param        $url
	 * @param array  $param  参数数组
	 * @param string $cookie 数组或'cookie 标准化字符串'
	 *
	 * @return mixed
	 *
	 */
	public static function httpGet( $url, $param = [], $cookie = '' ) {
		if ( $param ) {
			$url .= '?' . self::ArrToGetParam( $param );
		}
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_TIMEOUT_MS, 30000 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		if ( is_array( $cookie ) ) {
			$cookie = self::ArrayToCookieString( $cookie );
		}
		curl_setopt( $ch, CURLOPT_COOKIE, $cookie );
		$file_contents = curl_exec( $ch );

		curl_close( $ch );

		return $file_contents;
	}

	/**
	 * post方式请求资源
	 *
	 * @param     $url
	 * @param     $data
	 * @param int $timeout
	 *
	 * @return mixed
	 */
	public static function httpPost( $url, $data, $timeout = 60, $cookie = '' ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );

		curl_setopt( $ch, CURLOPT_POST, 1 );
		if ( $data != '' ) {

			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

		}

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		if ( $cookie && is_array( $cookie ) ) {
			$cookie = self::ArrayToCookieString( $cookie );
		}
		curl_setopt( $ch, CURLOPT_COOKIE, $cookie );

		$file_contents = curl_exec( $ch );
		curl_close( $ch );

		return $file_contents;
	}

	public static function microtime_float() {
		list( $usec, $sec ) = explode( " ", microtime() );

		return ( (float) $usec + (float) $sec );
	}

	/**
	 * http协议判断，并返回协议值
	 * 返回 https://或http://
	 *
	 * @param string $server_protocol
	 *
	 * @return string
	 */
	public static function server_protocol( $server_protocol = '' ) {
		$server_protocol || $server_protocol = self::getArrVal( 'SERVER_PROTOCOL', $_SERVER );
		if ( strpos( strtolower( $server_protocol ), 'https' ) !== false ) {
			return 'https://';
		}

		return 'http://';
	}


	/**
	 * 判断是不是cli运行模式
	 * @return bool
	 */
	public static function isCli(){
		if ( php_sapi_name() == 'cli' ) {
			return true;
		}
		return false;
	}

	/**
	 * 判断是否是微信访问
	 *
	 * @return bool
	 */
	public static function isWeiXin() {
		$userAgent = self::getArrVal( 'USER-AGENT', $_SERVER );
		$userAgent || $userAgent = self::getArrVal( 'HTTP_USER_AGENT', $_SERVER );
		$res = strpos( $userAgent, 'MicroMessenger' );
		if ( $res === false ) {
			return false;
		}

		return true;
	}

	/**
	 * 格式化字符串
	 *
	 * @param string $inStr      输入的字符串
	 * @param int    $formatType 格式化的类型
	 *                           必然会做的处理:去除js代码,表情符号和首尾空格
	 *                           1：去除字符串中的特殊符号，并将多个空格缩减成一个英文空格
	 *                           2：将字符串中的连续多个空格缩减成一个英文空格
	 *                           3：去除前后空格
	 *
	 * @return string
	 */
	public static function filterStr( $inStr, $formatType = 1 ): string {
		if ( strlen( $inStr . '' ) > 0 ) {
			$patterns = [
				"'<script[^>]*?>.*?</script>'si",
				'/[\xf0-\xf7].{3}/',
			];
			$replaces = [
				"",
				'',
			];
			if ( $formatType == 1 ) {
				$patterns[] = '/[\\\%\'\"\<\>\?\@\&\^\$\#\_]+/';
				$patterns[] = '/\s+/';
				$replaces[] = '';
				$replaces[] = ' ';
			} else if ( $formatType == 2 ) {
				$patterns[] = '/\s+/';
				$replaces[] = ' ';
			}

			$saveStr = preg_replace( $patterns, $replaces, $inStr );

			return trim( $saveStr );
		}

		return '';
	}

    /**
     * 检测金额数值
     *
     * @param $money
     *
     * @return mixed
     */
    public static function chkMoney( $money ) {
        if(!preg_match('^([1-9]\d{0,9}|0)([.]?|(\.\d{1,2})?)$', $money)){
            return false;
        }

        return true;
    }

	/**
	 * 金额数值模式化
	 *
	 * @param $money
	 *
	 * @return mixed
	 */
	public static function moneyFormat( $money ) {
		return number_format( $money, 2 );
	}

	/**
	 * 分转元
	 *
	 * @param $money
	 *
	 * @return mixed
	 */
	public static function fenToYuan( $money ) {
		return self::moneyFormat( $money / 100 );
	}

	/**
	 * 元转分
	 *
	 * @param $money
	 *
	 * @return mixed
	 */
	public static function yuanToFen( $money ) {
		return self::moneyFormat( $money * 100 );
	}


	/**
	 * 获取cli 输入的选项值 如 php -p p_value
	 *
	 * @param      $key
	 * @param null $default
	 *
	 * @return null
	 */
	public static function getCliOpt( $key, $default = null ) {
		$key = '-' . $key;
		global $argv;
		$_argv = $argv;
		unset( $_argv[0] );
		$optVal = '';
		foreach ( $_argv as $_k => $item ) {
			if ( $key == $item ) {
				if ( isset( $_argv[ $_k + 1 ] ) ) {
					$optVal = $_argv[ $_k + 1 ];
					break;
				}
			}
		}
		if ( $optVal ) {
			return $optVal;
		} else if ( ! is_null( $default ) ) {
			return $default;
		}

		return null;
	}

	/**
	 * array转xml
	 *
	 * @param array $dataArr
	 *
	 * @return string
	 */
	public static function arrayToXml( array $dataArr ): string {
		$xml = "<xml>";
		foreach ( $dataArr as $key => $eData ) {
			if ( is_numeric( $eData ) ) {
				$xml .= "<" . $key . ">" . $eData . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $eData . "]]></" . $key . ">";
			}
		}
		$xml .= "</xml>";

		return $xml;
	}

	/**
	 * xml转为array
	 *
	 * @param string $xml
	 *
	 * @return array
	 */
	public static function xmlToArray( string $xml ) {
		$obj = simplexml_load_string( $xml, 'SimpleXMLElement', LIBXML_NOCDATA );

		return json_decode( json_encode( $obj ), true );
	}

	/**
	 * RSA签名
	 *
	 * @param array  $data             待签名数据
	 * @param string $private_key_path 商户私钥文件路径
	 *
	 * @return string 签名结果
	 */
	public static function rsaSign( array $data, string $private_key_path ): string {
		$priKey = file_get_contents( $private_key_path );
		$res    = openssl_get_privatekey( $priKey );
		openssl_sign( $data, $sign, $res );
		openssl_free_key( $res );

		//base64编码
		return base64_encode( $sign );
	}

	/**
	 * RSA验签
	 *
	 * @param string $data            待签名数据
	 * @param string $public_key_path 公钥文件路径
	 * @param string $sign            要校对的的签名结果
	 *
	 * @return boolean 验证结果
	 */
	public static function rsaVerify( string $data, string $public_key_path, string $sign ): bool {
		$pubKey = file_get_contents( $public_key_path );
		$res    = openssl_get_publickey( $pubKey );
		$result = (boolean) openssl_verify( $data, base64_decode( $sign ), $res );
		openssl_free_key( $res );

		return $result;
	}

	/**
	 * RSA解密
	 *
	 * @param string $content          需要解密的内容，密文
	 * @param string $private_key_path 私钥文件路径
	 *
	 * @return string 解密后内容，明文
	 */
	public static function rsaDecrypt( string $content, string $private_key_path ): string {
		$priKey = file_get_contents( $private_key_path );
		$res    = openssl_get_privatekey( $priKey );
		//用base64将内容还原成二进制
		$content2 = base64_decode( $content );
		//把需要解密的内容，按128位拆开解密
		$result = '';
		$length = strlen( $content2 ) / 128;
		for ( $i = 0; $i < $length; $i ++ ) {
			$data = substr( $content2, $i * 128, 128 );
			openssl_private_decrypt( $data, $decrypt, $res );
			$result .= $decrypt;
		}
		openssl_free_key( $res );

		return $result;
	}

	/**
	 * md5签名字符串
	 *
	 * @param string $needStr 需要签名的字符串
	 * @param string $key     私钥
	 *
	 * @return string 签名结果
	 */
	public static function md5Sign( string $needStr, string $key ): string {
		return md5( $needStr . $key );
	}

	/**
	 * md5验证签名
	 *
	 * @param string $needStr 需要签名的字符串
	 * @param string $sign    签名结果
	 * @param string $key     私钥
	 *
	 * @return boolean 签名结果
	 */
	public static function md5Verify( string $needStr, string $sign, string $key ): bool {
		$thisSign = md5( $needStr . $key );

		return ( $thisSign == $sign ) ? true : false;
	}


	public static function serialize( $data ) {
		//return \swoole_serialize::pack($data);
		return serialize( $data );
	}

	public static function unserialize( $data ) {
//		return \swoole_serialize::unpack( $data );
		$res = unserialize( $data );

		return $res;
	}

	/**
	 * 获取剩余天数
	 *
	 * @param $endTime
	 *
	 * @return float|int
	 */
	public static function getDayNumber($endTime){
	    $endTime=(int)$endTime;
	    if(!$endTime)return 0;
	    return ceil(($endTime-time())/86400);
	}

	/**
	 * 返回今天的时间截 $isDate=true 时，返回日期格式
	 * @param bool $isDate
	 *
	 * @return false|int|string
	 */
	public static function getTodayTime($isDate=false){
		if($isDate){
			return date( 'Y-m-d' );
		}
	    return strtotime(date('Y-m-d'));
	}

	/**
	 * 返回今天剩余多少秒
	 * @return false|int
	 */
	public static function getTodayLastTimeLength(){
		return strtotime(date('Y-m-d 23:59:59'))-time();
	}

	/**
	 * 获取每周星期一时间戳
	 */
	public static function getMondayTime(){

		$today = self::getTodayTime();
		$n =(int) date( 'N');
		$monday=$today-(($n-1)*86400);

		return $monday;
	}

	/**
	 * 返回当月的时间戳
	 * @param bool $isDate
	 *
	 * @return false|int|string
	 */
	public static function getMonthTime($isDate=false){
		if($isDate){
			return date( 'Y-m' );
		}
		return strtotime(date('Y-m'));
	}

	private static $TemTree;

	static function GetTree($arr,$pid='',$pidKey='pid',$textKey='title',$idKey='id',$step=1){
		foreach($arr as $key=>$val) {
			if($val[$pidKey] === $pid) {
				$flg = str_repeat('　',$step);
				$val[$textKey] = $flg.$val[$textKey];
				self::$TemTree[] = $val;
				unset($arr[$key]);
				self::GetTree($arr , $val[$idKey] ,$pidKey,$textKey,$idKey,$step+1);
			}
		}
		return self::$TemTree;
	}

	static function is_mobile_client(){
		$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$useragent_commentsblock = preg_match('|\(.*?\)|', $useragent, $matches) > 0 ? $matches[0] : '';
		$mobile_os_list = array(
			'Google Wireless Transcoder',
			'Windows CE',
			'WindowsCE',
			'Symbian',
			'Android',
			'armv6l',
			'armv5',
			'Mobile',
			'CentOS',
			'mowser',
			'AvantGo',
			'Opera Mobi',
			'J2ME/MIDP',
			'Smartphone',
			'Go.Web',
			'Palm',
			'iPAQ'
		);

		$mobile_token_list = array(
			'Profile/MIDP',
			'Configuration/CLDC-',
			'160×160',
			'176×220',
			'240×240',
			'240×320',
			'320×240',
			'UP.Browser',
			'UP.Link',
			'SymbianOS',
			'PalmOS',
			'PocketPC',
			'SonyEricsson',
			'Nokia',
			'BlackBerry',
			'Vodafone',
			'BenQ',
			'Novarra-Vision',
			'Iris',
			'NetFront',
			'HTC_',
			'Xda_',
			'SAMSUNG-SGH',
			'Wapaka',
			'DoCoMo',
			'iPhone',
			'iPod',
			'iPad',
			'HUAWEI',
			'Coolpad'
		);

		foreach($mobile_os_list as $os){
			if(strpos($useragent_commentsblock, $os) !== false){
				return true;
			}
		}
		foreach($mobile_token_list as $token){
			if(strpos($useragent_commentsblock, $token) != false){
				return true;
			}
		}
		return false;
	}


}