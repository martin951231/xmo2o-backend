<?php


namespace app\foodshop\model\db;

use think\Model;
class ReplyPic extends Model
{
//插入数据
    public function insert_record($data){

        return $this->insertGetId($data);
    }
}