<?php
/**
 * @author : liukezhu
 * @date : 2021/11/26
 */
namespace app\community\model\service;

use app\community\model\db\HouseVillagePaymentStandardBind;

class HouseVillagePileEquipmentService{

    public $device_type_info=[
        0 => array(
            'device_type' => 2,
            'name' => 'A1智能-充电桩'
        ),
        1 => array(
            'device_type' => 1,
            'name' => '驴充充-充电桩'
        ),
        2 => array(
            'device_type' => 21,
            'name' => '艾特充-充电桩'
        ),
    ];
}