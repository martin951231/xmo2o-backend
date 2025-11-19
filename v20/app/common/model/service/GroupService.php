<?php
/**
 * 团购商品
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/29 13:47
 */

namespace app\common\model\service;

use app\common\model\db\Group;

class GroupService
{
    public $groupObj = null;
    public function __construct()
    {
        $this->groupObj = new Group();
    }

    /**
     * 获取团购商品表信息
     * User: chenxiang
     * Date: 2020/5/29 13:55
     * @param array $where
     * @param bool $field
     * @return mixed
     */
    public function getGroupData($where= [], $field = true) {
        $result = $this->groupObj->field($field)->where($where)->find();
        return $result;
    }
}