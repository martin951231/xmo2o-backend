<?php


namespace app\mall\model\db;

use think\Model;
class MallGoodsWords extends Model
{
   //获取列表
    public function getGoodsWordTypeList($where){
        return $this->where($where)->select()->toArray();
    }
}