<?php
/**
 * 系统后台导航分类表
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/21 11:38
 */

namespace app\common\model\db;
use think\Model;
class SliderCategory extends Model {
    /**
     * 根据分类key获导航分类
     * @param $catKey
     * @return \think\Collection
     */
    public function getSliderCategoryByCatKey($catKey) {
        if(empty($catKey)) {
            return false;
        }

        $where = [
            "cat_key" => trim($catKey),
        ];

        $result = $this->where($where)->find();
        return $result;
    }
}