<?php
/**
 * MallAdverCategory.php
 * 新版商城首页装修model
 * Create on 2020/10/20 15:11
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use \think\Model;

class MallAdverCategory extends Model
{
    /**
     * @param $field
     * @param $where
     * @param $order
     * @return array
     * 根据条件获取
     */
    public function getByCondition($field, $where, $order)
    {
        $arr = $this->field($field)->where($where)->order($order)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $field
     * @param $where
     * @return array
     * 根据id获取
     */
    public function getById($field, $where)
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 根据分类key获广告分类
     * @param $catKey
     */
    public function getAdverCategoryByCatKey($catKey)
    {
        $where = ["cat_key" => trim($catKey)];
        $arr = $this->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}