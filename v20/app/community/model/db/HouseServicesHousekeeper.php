<?php


namespace app\community\model\db;

use think\Model;

class HouseServicesHousekeeper extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getList($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }
}