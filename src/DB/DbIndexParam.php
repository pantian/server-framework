<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/7/17
 * Time: 20:19
 */

namespace PTLibrary\DB;


use Bin\Exception\ThrowException;

/**
 * 数据库索引参数类
 *
 * Class DbIndexParam
 *
 * @package DB
 */
class DbIndexParam {
	/**
	 * 字段名 多个字段，用','隔开
	 *
	 * @var string
	 */
	public $filed = '';
	/**
	 * 索引名称
	 * @var string
	 */
	public $indexName = '';

	/**
	 * 类型
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * 备注
	 *
	 * @var string
	 */
	public $comment = '';

	/**
	 * 索引类型：唯一
	 */
	const TYPE_UNIQUE = 'UNIQUE';
	/**
	 * 全文索引
	 */
	const TYPE_FULLTEXT = 'FULLTEXT';
	/**
	 * 空间索引
	 */
	const TYPE_SPATIAL = 'SPATIAL';
	/**
	 * 普通索引
	 */
	const TYPE_INDEX = 'INDEX';
	/**
	 * 主键索引
	 */
	const TYPE_PRIMARY_KEY = 'PRIMARY KEY';


	private static $instance;

	/**
	 * @param $is_instance
	 * @return \DB\DbIndexParam
	 */
	public static function instance($is_instance=true){
	    if(!self::$instance || !$is_instance){
		    self::$instance = new self();
	    }
	    self::$instance->init();

		return self::$instance;
	}

	private function __construct() {
	}
	public function init(){
		$this->indexName= '';
		$this->type = self::TYPE_INDEX;

	}

	/**
	 * 检测字段安全
	 * @throws \Exception\DBException
	 */
	public function chkParam() {
		if(!$this->filed || !is_string($this->filed)){
			ThrowException::DBException( 5001300, '字段名不为能空或无效' );
		}
		if(!$this->type || !is_string($this->type)){
			ThrowException::DBException( 5001301, '索引类型不为能空或无效' );
		}

	}

}