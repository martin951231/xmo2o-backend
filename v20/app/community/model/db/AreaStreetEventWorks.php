<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetEventWorks extends Model
{
    /**
     * @author lijie
     * @date_time 2021/03/08
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }
}