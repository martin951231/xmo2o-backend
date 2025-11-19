<?php
/**
 * 新版团购广告分类
 * Author: 钱大双
 * Date Time: 2021-1-20 14:49:09
 */

namespace app\group\model\service;

use app\group\model\db\GroupAdverCategory as GroupAdverCategoryModel;

class GroupAdverCategoryService
{
    public $groupAdverCategoryModel = null;

    public function __construct()
    {
        $this->groupAdverCategoryModel = new GroupAdverCategoryModel();
    }


    public function getCount($where)
    {
        $count = $this->groupAdverCategoryModel->getCount($where);
        return $count;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->groupAdverCategoryModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取广告数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true, $order = true)
    {
        if (empty($where)) {
            return [];
        }

        $result = $this->groupAdverCategoryModel->getSome($where, $field, $order);

        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }
}