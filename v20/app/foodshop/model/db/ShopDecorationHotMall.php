<?php

namespace app\foodshop\model\db;

use think\Model;

class ShopDecorationHotMall extends Model{
    // 获取热门搜索词
    public function HotMall($where,$fields='*'){
        
         $result = $this->where($where)->field($fields)->select()->toArray();
        //  dd($result);
        // echo $result->getLastSql();
        
        return $result;
    }
}