<?php
/**
 * GoodsCategory.php
 * 批发市场分类（用作链接库)
 * Create on 2020/11/17 15:23
 * Created by zhumengqun
 */

namespace app\common\model\db;

use \think\Model;

class GoodsWholesaleCategory extends Model
{
    /**
     * @return array
     * 获取全部分类
     */
    public function getCategory()
    {
        $arr = $this->field(true)->where(['status' => 1])->order('sort DESC')->select();
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