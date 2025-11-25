<?php

namespace app\shop\model\db;

use think\Model;

class ShopCategoryRelation extends Model{

    // 获取店铺数量
    public function getShopNumer($cat_id, $cat_fid, $fields='*'){

        if(empty($cat_fid)){
            $where = array(
                'cat_fid' => $cat_id
            );
        }else{
            $where = array(
                'cat_fid' => $cat_fid,
                'cat_id' => $cat_id
            );
        }

        $result = $this->where($where)->field($fields)->count();

        return $result;
    }
    

    // 获取分类对应店铺列表
    public function getShopStoreId($cat_id, $cat_fid, $fields='*'){

        if(empty($cat_fid)){
            $where = array(
                'cat_fid' => $cat_id
            );
        }else{
            $where = array(
                'cat_fid' => $cat_fid,
                'cat_id' => $cat_id
            );
        }

        $result = $this->where($where)->field($fields)->select()->toArray();

        return $result;
    }
    
}