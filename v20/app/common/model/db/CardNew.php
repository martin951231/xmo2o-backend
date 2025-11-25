<?php
/**
 * 商家会员卡表
 * User: lumin
 */

namespace app\common\model\db;

use think\Model;

class CardNew extends Model
{
    public function getOne($where = []){
        return $this->where($where)->find();
    }
}