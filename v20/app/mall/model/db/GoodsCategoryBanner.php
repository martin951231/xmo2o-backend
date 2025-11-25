<?php
/**
 * GoodsCategoryBanner.php
 * 平台后台-分类列表-轮播图
 * Create on 2020/9/14 10:23
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class GoodsCategoryBanner extends Model
{
    public function getBannerList($where, $field = true)
    {
        $arr = $this->field($field)->where($where)->select()->toArray();
        return $arr;
    }

    public function updateBanner($arr, $where)
    {
        $result = $this->where($where)->update($arr);
        return $result;
    }

    public function addBanner($arr)
    {
        $result = $this->insert($arr);
        return $result;
    }

    public function delBanner($where)
    {
        $result = $this->where($where)->delete();
        return $result;
    }
}