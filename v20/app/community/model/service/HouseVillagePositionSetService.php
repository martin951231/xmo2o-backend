<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2023/10/27 9:20
 */

namespace app\community\model\service;

use app\community\model\db\HouseVillagePositionSet;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
use think\facade\Db;

class HouseVillagePositionSetService
{
    use CommonLogTraits;
    use AdminLoginTraits;
    public function getOneData($where, $field = true)
    {

        $houseVillagePositionSetDb = new HouseVillagePositionSet();
        $info = $houseVillagePositionSetDb->getOneData($where, $field);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        } else {
            $info = array();
        }
        return $info;
    }
    public function savePositionSet($village_id=0,$data=array()){
        $houseVillagePositionSetDb = new HouseVillagePositionSet();
        $whereArr=array('village_id'=>$village_id);
        $info = $houseVillagePositionSetDb->getOneData($whereArr, '*');
        if ($info && !$info->isEmpty()) {
            $ret = $houseVillagePositionSetDb->saveOneData($whereArr, $data);
        } else {
            $data['village_id']=$village_id;
            $ret = $houseVillagePositionSetDb->addOneData($data);
        }
        $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
        $queuData = [
            'logData' => [
                'tbname' => '抢购租赁订单-限时设置',
                'table'  => 'house_village_position_set',
                'client' => '小区后台',
                'trigger_path' => '停车管理->抢购租赁订单',
                'trigger_type' => $this->getUpdateNmae(),
                'addtime'      => time(),
                'village_id'   => $village_id,
                'property_id' =>0,
                'op_id'   => $parseInfo['op_id'],
                'op_type'   => $parseInfo['op_type'],
                'op_name'   => $parseInfo['op_name'],
            ],
            'newData' => $data,
            'oldData' =>[]
        ];
        $this->laterLogInQueue($queuData);
        return $ret;
    }
    public function saveOneData($whereArr, $data)
    {
        $houseVillagePositionSetDb = new HouseVillagePositionSet();
        $ret = $houseVillagePositionSetDb->saveOneData($whereArr, $data);
        return $ret;
    }

    public function addOneData($data)
    {
        $houseVillagePositionSetDb = new HouseVillagePositionSet();
        $ret = $houseVillagePositionSetDb->addOneData($data);
        return $ret;
    }

}
