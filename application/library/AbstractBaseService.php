<?php

/**
 * 基处理service层
 * Class BaseService
 * @author xiongba
 * @date 2020-02-20 17:40:28
 */
abstract class AbstractBaseService
{

    /**
     * @var \Yaf\Config\Ini
     */
    protected $config;
    /**
     * @var LibMember
     */
    protected $User;
    /**
     * @var array|bool
     */
    protected $member;
    /**
     * @var array
     */
    protected $post;
    /**
     * @var integer
     */
    protected $page, $limit, $offset;

    protected $position;

    final public function __construct()
    {
        $this->__init();
        $this->_init();;
    }

    final public function __init()
    {
        $this->config = register('controller')->config;
        /**获取用户信息**/
        $this->member = register('controller')->member;
        $this->page = register('controller')->page;
        $this->post = &$_POST;
        /**分页参数**/
        $this->limit = register('controller')->limit;
        $this->offset = register('controller')->offset;
        /**位置信息**/
        $this->position = register('controller')->position;
    }


    protected function _init()
    {

    }

    /**
     * @return $this
     */
    final public function newInstance()
    {
        return new static();
    }

}