<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/9/21
 * Time: 17:28
 */

namespace DB\Mongodb;

use Request\Request;
class MongoModel extends MongodbBase
{
    /**
     * 分页链接key
     *
     * @var string
     */
    private $_pageUrl_key   = 'page';
    private $_page_size_key = 'pagesize';
    /**
     * 默认数据
     * @var array
     */
    protected $_defaultValue = [];
    protected $_filedData=[];
    function __construct()
    {
        parent::__construct();

    }

    function setFiledData($fieldData)
    {
        $this->_filedData=$fieldData;
        if(!empty($this->_defaultValue)){
            $this->_filedData=array_merge($this->_defaultValue,$fieldData);
        }

        return $this;
    }

    protected function getFiledData()
    {
        $this->_filedData;
    }

    function add( array $data )
    {
        $this->setFiledData($data);
        return parent::add($this->_filedData);
    }

    /**
     * 分页查询
     *  返回: array('data'=>'','page'=>'')
     *
     * @param int $size
     *
     * @return array
     */
    public function findPage( $size = 20 )
    {
        $list['page'] = $this->getPageInfo();
        $res          = $this->limit($this->_pageSize,(($this->_currentPage-1)*$this->_pageSize))->select();
        $list['data'] = $res;
        unset($res);

        return $list;
    }

    /**
     * @param int $currentPage
     */
    public function setCurrentPage( $currentPage )
    {
        if ( !$currentPage ) {
            $currentPage = 1;
        }
        $this->_currentPage = $currentPage;
    }

    /**
     * 获取当前页
     *
     * @return bool|null
     */
    public function getCurrentPage()
    {
        $page = (int)Request::get( $this->_pageUrl_key,1);
        if($page<=0){
            $page=1;
        }
        $this->_currentPage=$page;
        return $page;
    }

    /**
     * 获取每页记录大小
     *
     * @return int
     */
    public function getPageSize()
    {
        $pageSize = (int)Request::get( $this->_page_size_key , $this->_pageSize);
        if($pageSize <= 0){
            $pageSize = $this->_pageSize;
        }
        $this->_pageSize=$pageSize;
        return $pageSize;
    }

    /**
     * 获取分页信息
     *
     * @return mixed
     */
    public function getPageInfo()
    {
        $info['current']     = $this->getCurrentPage();
        $info['pageSize']    = $this->getPageSize();
        $info['countRecord'] = $this->count($this->_where);
        $info['totalPage']   = ceil( $info['countRecord'] / $info['pageSize'] );

        return $info;
    }

}