<?php

namespace app\shop\model\db;

use think\Model;

class ShopCategory extends Model{
    
    // 获取分类列表
    public function getLableList($fields='*'){
        
        $result = $this->field($fields)->select()->toArray();

        return $result;
    }
    
    // 获取指定分类
    public function getOneLable($cat_id, $fields='*'){
        
        $result = $this->where(array('cat_id'=>$cat_id))->field($fields)->find()->toArray();

        return $result;
    }

    /**
     * 查询所有分类
     */
    public function getLableAll($where,$fields='a.*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('a')
            ->join($prefix.'shop_category b', 'a.cat_id = b.cat_fid')
            ->where($where)
            ->field($fields)
            ->order('a.cat_sort desc,b.cat_sort desc')
            ->select()
            ->toArray();
        return $result;
    }
}