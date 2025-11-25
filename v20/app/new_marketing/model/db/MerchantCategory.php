<?php

namespace app\new_marketing\model\db;

use think\Model;

class MerchantCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getStoreTypeList($where) {
        $list = $this->where($where)->field('cat_id as value,cat_name as label')->order('cat_sort desc')->select()->toArray();
        return $list;
    }

    public function getOneData($where) {
        $data = $this->where($where)->find();
        return $data;
    }

    public function getStoreFcatIds($where) {
        $list = $this->where($where)->column('cat_id');
        return $list;
    }

}