<?php
/**
 * ShopCategory.php
 * 外卖分类（用作链接库）
 * Create on 2020/11/17 10:46
 * Created by zhumengqun
 */

namespace app\common\model\db;

use \think\Model;

class ShopCategory extends Model
{
    /**
     * @return array
     * 获取全部分类
     */
    public function getCategory()
    {
        $arr = $this->field(true)->where(['cat_status'=>1])->order('cat_sort DESC')->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @return array
     * 根据条件获取一个
     */
    public function getOne($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}