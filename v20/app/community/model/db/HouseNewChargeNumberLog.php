<?php
/**
 * 新版收费-科目变更记录
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseNewChargeNumberLog extends Model
{
    public function getList($where,$field,$order,$page=0,$limit=0)
    {
        $list = $this->where($where)
            ->field($field)
            ->order($order)
            ->paginate($limit)
            ->toArray();
        return $list;
    }
    
    public function addData($data){
        $add = $this->insert($data);
        return $add;
    }
}
