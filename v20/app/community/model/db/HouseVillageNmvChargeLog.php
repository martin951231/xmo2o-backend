<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/12/13
 * Time: 17:20
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class HouseVillageNmvChargeLog extends Model
{
    /**
     * 插入记录
     * @param $data
     * @return int|string
     */
    public function addLog($data){
        return $this->insert($data);
    }
}