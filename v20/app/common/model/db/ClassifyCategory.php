<?php
/**
 * GoodsCategory.php
 * 分类信息分类（用作链接库)
 * Create on 2020/11/17 15:23
 * Created by zhumengqun
 */

namespace app\common\model\db;

use \think\Model;

class ClassifyCategory extends Model
{
    const CATE_LEVEL_2 = 2;//二级分类
    /**
     * @return array
     * 获取全部分类
     */
    public function getCategory()
    {
        $arr = $this->field(true)->where(['cat_status' => 1])->order('cat_sort DESC')->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
    
    public function searchFcidAttr($query, $value)
    {
        $value && $query->where('fcid', $value);
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
    
    public function classifyCategory($level = 1, $params = [])
    {
        return ClassifyCategory::where(['cat_status' => 1])
            ->withSearch(['fcid'], $params)
            ->where(['subdir' => $level])
            ->order('cat_sort DESC')
            ->field('cid,cat_name,cat_url')
            ->select();
    }
}