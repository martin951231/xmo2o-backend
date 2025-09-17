<?php


namespace app\mall\model\db;
use think\Model;
class MallSearchFind extends Model
{
   public function getContent(){
       $where = [
           'is_del' => 0
       ];
       return $this->field("find_id,find_name")->where($where)->select();
   }
}