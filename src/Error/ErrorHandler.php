<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/26 0026
 * Time: 10:57
 */
namespace PTLibrary\Error;

/**
 * 错误代码
 * Class ErrorHandler
 *
 * @package Bin\Error
 */
class ErrorHandler {
	const COMMON_SUCCESS = 0;
	const COMMON_ERROR_MIN_NUM = 10000;
	const ERROR = 10000;
	const NO_USER = 10001;
	const SAVE_FAIL = 10002;
	const USER_ID_EMPTY = 10003;
	const PASSWORD_ERROR = 10004;
	const PASSWORD_LENGTH_ERROR = 10005;
	const PASSWORD_EMPTY = 10006;
	const OLD_PASSWORD_ERROR = 10007;
	const OLD_PASSWORD_EMPTY = 10008;
	const NEW_PASSWORD_EMPTY = 10009;
	const DATA_ERROR = 10010;
	const REQUEST_METHOD_NOT_POST = 10011;
	const RECHECK_PASSWORD_ERROR = 10012;
	const COMMON_CHECK = 10013;
	const COMMON_SERVER = 10014;
	const COMMON_NOT_EXIST = 10015;
	const COMMON_API_AUTH_FAIL = 10016;
	const COMMON_PARAMS_ERROR = 10017;
	const COMMON_RESPONSE_DATA_ERROR = 10018;
	const CLASS_EXIST=10020;
	//会员
	const MEMBER_STATUS_NORMAL = 20000;
	const MEMBER_STATUS_NOT_AUDIT = 20001;
	const MEMBER_STATUS_NOT_PASS_AUDIT = 20002;
	const MEMBER_STATUS_DISABLE = 20003;
	const MEMBER_NO_EXIST = 20004;
	const MEMBER_DISABLE_LOGIN = 20005;
	const MEMBER_SERVICE_NOT_EXIST = 20006;
	const MEMBER_INFO_GET_FAIL = 20007;
	const MEMBER_MONEY_SUFFICIENT = 20008;
	const MEMBER_LOGIN_TIMEOUT = 20009;
	const MEMBER_IS_LOGIN = 20010;
	const MEMBER_NAME_EMPTY = 20011;
	const MEMBER_UID_LENGTH_INVALID = 20012;
	const USER_ID_INVALID=20013;
	const USER_QR_CODE                 = 20014;
	const MEMBER_NOT_LOGIN             = 20016;
	//邮箱
	const EMAIL_STATUS_NORMAL = 20020;
	const EMAIL_STATUS_NOT_PASS_AUDIT = 20021;
	const EMAIL_IS_EXIST = 20022;
	const EMAIL_EMPTY = 20023;
	const EMAIL_FORMAT_ERROR = 20024;
	//手机号码
	const MOBILE_STATUS_NORMAL = 20030;
	const MOBILE_STATUS_NOT_PASS_AUDIT = 20031;
	const MOBILE_EMPTY = 20032;
	const MOBILE_FORMAT_ERROR = 20033;
	const MOBILE_IS_EXIST = 20034;

	const VERIFY_CODE_EMPTY = 20040;
	const VERIFY_CODE_ERROR = 20041;
	const VERIFY_CODE_DISABLE = 20042;
	const VERIFY_CODE_FREQUENTLY = 20043;
	//数据库
	const DB_SAVE_FAIL = 20050;
	const DB_ERROR = 20051;
	const DB_PASSWORD_EMPTY = 20052;
	const DB_CONNECT_FAIL = 20053;
	const DB_TABLE_EMPTY = 20054;
	const DB_INSERT_FAIL = 20055;
	const DB_DELETE_FAIL = 20056;
	const DB_SELECT_FAIL = 20057;
	const DB_PDO_EMPTY = 20058;
	const DB_TABLE_EXIST = 20059;
	const DB_SQL_PREPARE = 20200;
	const DB_SET_INC_FAIL=202001;


	const ACCOUNT_ERROR = 20060;
	const ACCOUNT_IS_REGISTER = 20061;

	const GET_DATA_FILED = 20070;
	const TABLE_MODEL_EMPTY = 20071;


	//订单
	const ORDER_FEE_INVALID = 20080;
	const ORDER_FULL_FEE_INVALID = 20081;
	const ORDER_TITLE_INVALID = 20082;
	const ORDER_UID_INVALID = 20083;
	const ORDER_TRACK_ON_INVALID = 20084;


	//URL
	const URL_DIRECT = 20075;

	//模型
	const MODEL_NOT_FIND = 20090;

	//程序exit
	const PARAM_EXIT = 20081;

	//控制器
	const GET_CONTROL_INSTANCE_EXCEPTION = 20090;
	const CREATE_CONTROL_EXCEPTION = 20091;
	const CONTROL_EXCEPTION = 20092;
	const REQUEST_INVALID = 20100;

	//参数
	const PARAM_ERROR = 20110;

	//权限
	const NO_PERMISSIONS = 20130;


	//mongodb
	const MONGODB_DB_NAME_EMPTY = 20210;
	const MONGODB_DB_PASSWORD_EMPTY = 20211;
	const MONGODB_DB_CONNECT_FAIL = 20212;
	const MONGODB_DB_TABLE_EMPTY = 20213;
	//mysql
	const DB_NAME_EMPTY=20220;



	//系统
	const SYSTEM_EXCEPTION = 30000;
	const NOT_PERMISSION=300001;
	//校验
	const VERIFY_REQUIRED = 30100;
	const VERIFY_EMAIL_INVALID = 30101;
	const VERIFY_MOBILE = 30102;
	const VERIFY_MAX_LENGTH = 30103;
	const VERIFY_MIN_LENGTH = 30104;
	const VERIFY_NUMBER = 30105;
	const VERIFY_MAX = 30106;
	const VERIFY_MIN = 30107;
	const VERIFY_STRING = 30108;
	const VERIFY_UNIQUE = 30109;
	const VERIFY_FAIL = 30110;
	const VERIFY_DATA_TYPE = 30111;
	const VERIFY_EXITED = 30112;
	const VERIFY_PATTERN = 30113;
	const VERIFY_DATA_INVALID = 30114;
	const VERIFY_BETWEEN_VALUE = 30115;
	const VERIFY_BETWEEN_LENGTH = 30116;
	const VERIFY_FORM_HASH = 30117;
	const VERIFY_RULE_INVALID = 30300;
	const VERIFY_LENGTH = 30310;


	//数据
	const NOT_DATA = 30120;

	//模型字段校验
	const DB_FIELD_VERIFY_EXCEPTION = 30210;
	//签名
	const SIGN_INVALID = 30220;
	const SIGN_INVALID_SIGN_NONCE_STR = 30221;
	const SIGN_INVALID_SIGN_TIME = 30222;
	const SIGN_ERROR = 30223;
	const SIGN_IS_USED = 30224;
	const SIGN_LENGTH_ERROR = 30225;
	//服务提供
	const SERVERS_REQUEST_FAIL = 30230;

	//微信错误
	const WX_PARAM_ERROR = 40300;
	const WX_POST_ERROR = 40301;
	const WX_GET_ERROR = 40302;
	//时间
	const TIME_PAST=40400;
	const TIME_UN_START=40401;
	const TIME_INVALIDITY=40402;



	public static $msg
		= [
			self::COMMON_SUCCESS        => '成功',
			self::ERROR                 => '错误',
			self::NO_USER               => '用户不存在',
			self::SAVE_FAIL             => '保存失败',
			self::USER_ID_EMPTY         => '用户ID为空',
			self::PASSWORD_ERROR        => '密码错误',
			self::PASSWORD_LENGTH_ERROR => '密码长度错误,长度在 6-16位之间',
			self::PASSWORD_EMPTY        => '密码为空',
			self::OLD_PASSWORD_ERROR    => '原密码错误',
			self::OLD_PASSWORD_EMPTY    => '原密码为空',
			self::NEW_PASSWORD_EMPTY    => '新密码为空',
			self::DATA_ERROR            => '数据错误',
			self::REQUEST_METHOD_NOT_POST => '不是POST请求',
			self::RECHECK_PASSWORD_ERROR  => '确认密码不一致',
			self::CLASS_EXIST=>'类不存在',
			self::USER_ID_INVALID=>'用户id无效',

			//会员
			self::MEMBER_STATUS_NORMAL    => '会员状态正常',
			self::MEMBER_STATUS_NOT_AUDIT => '会员状态未审核',
			self::MEMBER_STATUS_DISABLE   => '会员已被禁用',
			self::MEMBER_NO_EXIST         => '会员不存在',
			self::MEMBER_DISABLE_LOGIN    => '服务员不存在',
			self::MEMBER_INFO_GET_FAIL    => '获取用户信息失败',
			self::MEMBER_MONEY_SUFFICIENT => '余额不足',
			self::MEMBER_LOGIN_TIMEOUT    => '登录已超时',
			self::MEMBER_IS_LOGIN         => '已登录',
			self::MEMBER_NAME_EMPTY       => '名称为空',
			self::MEMBER_UID_LENGTH_INVALID       => '用户id长度无效',
			self::REQUEST_INVALID         =>'无效请求',
			self::USER_QR_CODE            =>'二维码无效',
			self::MEMBER_NOT_LOGIN        => '未登录',

			self::EMAIL_STATUS_NORMAL         => '邮箱状态正常',
			self::EMAIL_STATUS_NOT_PASS_AUDIT => '邮箱状态未通过验证',
			self::EMAIL_IS_EXIST              => '邮箱已存在',

			self::MOBILE_STATUS_NORMAL         => '手机号码状态正常',
			self::MOBILE_STATUS_NOT_PASS_AUDIT => '手机号码未通过验证',
			self::MOBILE_EMPTY                 => '手机号码为空',
			self::MOBILE_FORMAT_ERROR          => '手机号码格式错误',
			self::MOBILE_IS_EXIST              => '手机号码已存在',
			//验证码状态
			self::VERIFY_CODE_EMPTY            => '验证码为空',
			self::VERIFY_CODE_ERROR            => '验证码错误',
			self::VERIFY_CODE_DISABLE          => '验证码无效',
			self::VERIFY_CODE_FREQUENTLY       => '获取验证码过于频繁',

			//数据库失败
			self::DB_SAVE_FAIL                 => '数据库保存失败',
			self::DB_ERROR                     => '数据库出错',
			self::DB_PASSWORD_EMPTY            => '数据库密码为空',
			self::DB_CONNECT_FAIL              => '数据库链接失败',
			self::DB_TABLE_EMPTY               => '表名为空',
			self::DB_INSERT_FAIL               => '数据库插入失败',
			self::DB_DELETE_FAIL               => '数据库删除失败',
			self::DB_SELECT_FAIL               => '数据库查询异常',
			self::DB_PDO_EMPTY                 => 'PDO为空',
			self::DB_TABLE_EXIST               => '数据表不存在',
			self::DB_SQL_PREPARE               => 'sql预处理失败',
			self::DB_NAME_EMPTY => '数据库为空',
			self::DB_SET_INC_FAIL => '字段数值递增失败',


			//订单
			self::ORDER_FEE_INVALID              => '订单实付费用无效',
			self::ORDER_FULL_FEE_INVALID         => '订单应付费用无效',
			self::ORDER_TITLE_INVALID            => '订单标题无效',
			self::ORDER_UID_INVALID              => '订单用户UID无效',
			self::ORDER_TRACK_ON_INVALID         => '订单编号为空',

			//账号：
			self::ACCOUNT_ERROR                => '账号错误',
			self::ACCOUNT_IS_REGISTER          => '账号已被注册',

			//通用提示
			self::GET_DATA_FILED               => '获取数据失败',
			self::TABLE_MODEL_EMPTY            => '数据表模型为空',

			//参数
			self::PARAM_ERROR                  => '参数错误',

			//权限
			self::NO_PERMISSIONS                 => '没有权限',
			self::MONGODB_DB_CONNECT_FAIL        => 'mongo链接失败',
			self::MONGODB_DB_NAME_EMPTY          => 'mongo数据库为空',
			self::MONGODB_DB_PASSWORD_EMPTY      => 'mongo密码为空',
			self::MONGODB_DB_TABLE_EMPTY         => 'mongo表名为空',

			//URL
			self::URL_DIRECT                     => 'URL 跳转',

			//控制器
			self::GET_CONTROL_INSTANCE_EXCEPTION => '获取Controller 失败',
			self::CREATE_CONTROL_EXCEPTION       => '创建控制器异常',
			self::CONTROL_EXCEPTION              => '控制器异常',
			//校验
			self::VERIFY_REQUIRED                => '必填',
			self::VERIFY_EMAIL_INVALID           => '电子邮箱无效',
			self::VERIFY_MOBILE                  => '手机号码无效',
			self::VERIFY_MAX_LENGTH              => '最大长度',
			self::VERIFY_MIN_LENGTH              => '最小长度',
			self::VERIFY_NUMBER                  => '数字',
			self::VERIFY_MAX                     => '最大值',
			self::VERIFY_MIN                     => '最小值',
			self::VERIFY_STRING                  => '字符串',
			self::VERIFY_UNIQUE                  => '唯一值',
			self::VERIFY_FAIL                    => '校验失败',
			self::VERIFY_DATA_TYPE               => '数据类型无效',
			self::VERIFY_EXITED                  => '已存在',
			self::VERIFY_PATTERN                 => '正则不匹配',
			self::VERIFY_DATA_INVALID            => '校验规则无效',
			self::VERIFY_BETWEEN_VALUE           => '不在指定大小范围内',
			self::VERIFY_BETWEEN_LENGTH          => '不在指定长度范围内',
			self::VERIFY_RULE_INVALID            => '校验规则无效',
			self::VERIFY_FORM_HASH  =>'表单长时间没提交已过期',


			//数据
			self::NOT_DATA                       => '没有数据',
			//模型字段校验
			self::DB_FIELD_VERIFY_EXCEPTION      => '字段校验无效',
			//系统
			self::SYSTEM_EXCEPTION               => '系统异常',
			self::NOT_PERMISSION               => '没有权限',
			//签名
			self::SIGN_INVALID                   => '签名无效',
			self::SIGN_INVALID_SIGN_NONCE_STR    => '_sign_nonce_str 参数无效',
			self::SIGN_INVALID_SIGN_TIME         => '_sign_time 参数无效',
			self::SIGN_ERROR                     => '签名错误',
			self::SIGN_IS_USED                     => '签名已使用过',
			self::SIGN_LENGTH_ERROR                     => '签名值长度错误',


			//服务
			self::SERVERS_REQUEST_FAIL           => '服务请求失败',


			//微信
			self::WX_PARAM_ERROR => '微信参数错误',
			self::WX_POST_ERROR => '微信发送POST请求出错',
			self::WX_GET_ERROR => '微信发送GET请求出错',

			self::TIME_PAST => '已过期',
			self::TIME_UN_START => '未开始',
			self::TIME_INVALIDITY=>'无效',



		];

	public static function getErrMsg( $key ) {
		if (isset(self::$msg[$key])) {
			return self::$msg[$key];
		}

		return '';
	}
}