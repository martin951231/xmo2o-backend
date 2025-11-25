<?php
/**
 * 系统后台广告分类表
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/21 09:19
 */

namespace app\common\model\db;
use think\Model;
class AdverCategory extends Model {
    /**
     * 根据分类key获广告分类
     * @param $catKey
     * @return \think\Collection
     */
    public function getAdverCategoryByCatKey($catKey) {
        if(empty($catKey)) {
            return false;
        }

        $where = [
            "cat_key" => trim($catKey),
        ];

        $result = $this->where($where)->find();
        return $result;
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
}