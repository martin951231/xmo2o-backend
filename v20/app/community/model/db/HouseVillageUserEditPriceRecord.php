<?php


namespace app\community\model\db;

use think\Model;
class HouseVillageUserEditPriceRecord extends Model
{
    /**
     * 添加记录
     * @author lijie
     * @date_time 2020/11/30
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }
}