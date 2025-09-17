<?php
namespace app\common\model\db;

use think\Model;
class UserBehaviorReply extends Model {

    //插入数据
    public function insert_record($data){

        return $this->save($data);
    }
}