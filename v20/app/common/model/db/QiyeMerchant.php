<?php
/**
 * 企业商家
 * User: lumin
 */

namespace app\common\model\db;

use think\Model;

class QiyeMerchant extends Model
{
    public function getOne($where = []){
        return $this->where($where)->find();
    }
}