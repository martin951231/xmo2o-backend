<?php


namespace app\common\model\db;

use think\Model;

class MerchantCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    
    /**
     * @param $where
     * @param $column
     * @return array
     * 获取一级分类列表
     */
    public function getCategory(){
        $where = ['cat_fid'=>0,'cat_status'=>1];
        return $this->where($where)->order('cat_sort DESC, cat_id DESC')->select()->toArray();
    }

    /**
     * @param $where
     * @param $column
     * @return array
     * 取某个字段值
     */
    public function getCategoryName($where,$column){
        return $this->where($where)->column($column);
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function getDel($where){
        return $this->where($where)->delete();
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 获取某个字段值
     */
    public function getVal($where,$filed){
        return $this->where($where)->value($filed);
    }
}