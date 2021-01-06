<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/14 0014
 * Time: 11:02
 */

namespace PTLibrary\Verify;


use Bin\Error\ErrorHandler;
use Bin\Exception\VerifyException;

class VerifyRule {
	/**
	 * 最小值
	 */
	const RULE_MIN = 'min';
	/**
	 * 最大值
	 */
	const RULE_MAX = 'max';
	/**
	 * 最小长度
	 */
	const RULE_MIN_LENGTH = 'minlength';
	/**
	 * 最大长度
	 */
	const RULE_MAX_LENGTH = 'maxlength';
	/**
	 * 长度
	 */
	const RULE_LENGTH = 'length';
	/**
	 * 必填
	 */
	const RULE_REQUIRED = 'required';
	/**
	 * 是否是数值
	 */
	const RULE_NUMBER = 'number';
	/**
	 * 邮箱账号
	 */
	const RULE_EMAIL = 'email';
	/**
	 * 手机号码
	 */
	const RULE_MOBILE = 'mobile';
	/**
	 * 唯一性校验
	 */
	const RULE_UNIQUE = 'unique';
	/**
	 * 正则
	 */
	const RULE_PATTERN = 'pattern';
	/**
	 * 数值范围
	 */
	const RULE_BETWEEN = 'between';
	/**
	 * 长度范围
	 */
	const RULE_BETWEEN_LENGTH = 'betweenlength';
	/**
	 * 值范围
	 */
	const RULE_IN = 'in';
	/**
	 * url
	 */
	const RULE_URL = 'url';
	/**
	 * 签名
	 */
	const RULE_SIGN = 'sign';
	/**
	 * base64图片
	 */
	const RULE_BASE64_IMAGE = 'base64image';
	/**
	 * 表单hash值
	 */
	const RULE_FORM_HASH='formHash';

	/**
	 * 验证的字段
	 *
	 * @var string
	 */
	public $field = '';
	/**
	 * 校验值
	 *
	 * @var string
	 */
	public $value = '';
	/**
	 * 规则
	 *
	 * @var string
	 */
	public $ruleValue = '';
	/**
	 * 校验类型
	 * @var string
	 */
	public $ruleType = '';

	/**
	 * 错误信息
	 *
	 * @var string
	 */
	public $error = '';
	/**
	 * 最小值
	 *
	 * @var int
	 */
	public $minValue = 0;
	/**
	 * 最小值
	 *
	 * @var int
	 */
	public $maxValue = 0;
	/**
	 * 字段描述
	 *
	 * @var string
	 */
	public $description = '';
	/**
	 * 多个校验规则
	 * @var string
	 */
	public $rules = '';

	public function init() {
		$this->field       = '';
		$this->value       = '';
		$this->ruleValue        = '';
		$this->error       = '';
		$this->minValue    = 0;
		$this->maxValue    = 0;
		$this->description = '';
	}

	/**
	 * 获取检验描述
	 * @return string
	 */
	public function getDes(){
	    return $this->description?$this->description:$this->field;
	}

	/**
	 * 检验的数据类型检测
	 *
	 * @return bool
	 * @throws \Exception\VerifyException
	 */
	public function chkDataType() {
		if ( !is_object($this->value)&& !is_array($this->value)) {
			return true;
		}
		throw new VerifyException( ErrorHandler::VERIFY_DATA_TYPE, $this->description . '数据类型无效' );
	}

	/**
	 * 校验处理
	 */
	public function verify() {
		if($this->ruleType ){
			$methodName = ucfirst( $this->ruleType ) . 'Verify';

			if(!class_exists(VerifyFactory::class))return false;
			if(method_exists(VerifyFactory::class,$methodName)){
				$V                    = \Verify\VerifyFactory::$methodName();
				$V->doVerifyRule( $this);
			}
		}

	}

}