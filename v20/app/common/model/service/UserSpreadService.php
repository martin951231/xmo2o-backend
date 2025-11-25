<?php
/**
 * 用户推广关系
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/28 17:30
 */

namespace app\common\model\service;

use app\common\model\db\UserSpread;
use app\common\model\service\weixin\TemplateNewsService;

class UserSpreadService
{
    public $userSpreadObj = null;
    public function __construct()
    {
        $this->userSpreadObj = new UserSpread();
    }

    /**
     * 获取用户推广关系
     * User: chenxiang
     * Date: 2020/5/28 17:53
     * @param bool $field
     * @param array $where
     * @return array|mixed|\think\Model|null
     */
    public function getUserSpreadData($field = true, $where = []) {
        $result = $this->userSpreadObj->getUserSpreadData($field, $where);
        return $result;
    }

    /**
     * 更新某个字段
     * User: chenxiang
     * Date: 2020/6/1 14:10
     * @param array $where
     * @param string $field
     * @param string $value
     * @return mixed
     */
    public function setField($where = [], $field = '', $value = '') {
        $result = $this->userSpreadObj->setField($where, $field, $value);
        return $result;
    }


    /**
     * 获取记录
     * @author: 张涛
     * @date: 2020/8/25
     */
    public function getOneRow($where)
    {
        $row = $this->userSpreadObj->where($where)->find();
        return $row ? $row->toArray() : [];
    }

    /**
     * 添加记录
     * @date: 2021/11/04
     */
    public function add($data)
    {
        $row = $this->userSpreadObj->add($data);
        return $row;
    }
}