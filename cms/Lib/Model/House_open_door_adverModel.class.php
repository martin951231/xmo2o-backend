<?php
class House_open_door_adverModel extends Model
{
    // 智能硬件类型-对应修改信息的时候请对应修改下面的数组
    public $intelligent_hardware_type_arr = array(
        0 => '蓝牙门禁',
        1 => '人脸识别门禁',
        2 => '智能快递柜',
        3 => '停车管理',
        4 => '智能充电桩',
    );
    
    public function equipmentTypeArr()
    {
        $judgeFaceXiHanDoorSwitch = D('Config_customization')->getFaceXiHanDoorSwitch();
        $result = [];
        $result[] = ['name' => '蓝牙门禁', 'value' => 'Bluetooth'];
        if ($judgeFaceXiHanDoorSwitch) {
            $result[] = [
                ['name' => 'A1智能门禁机', 'value' => 'A1'],
                ['name' => 'A185智能门禁机', 'value' => '61'],
                ['name' => '西函智能门禁机', 'value' => '31'],
            ];
        } else {
            $result[] = [
                ['name' => 'A1智能门禁机', 'value' => 'A1'],
                ['name' => 'A185智能门禁机', 'value' => '61'],
            ];
        }
        $result[] = [
            ['name' => '10.1寸智能快递柜', 'value' => 'Express_cabinet_101'],
            ['name' => '21.5寸智能快递柜', 'value' => 'Express_cabinet_215'],
        ];
        $result[] = [
            ['name' => '停车管理', 'value' => 'Parking_management'],
            ['name' => '智能充电桩', 'value' => 'Charging_pile'],
        ];
        return $result;
    }
    
    // 对应只能硬件区分  设备类型
    public $equipment_type_arr = array(
        0 => array(
            'name' => '蓝牙门禁',
            'value' => 'Bluetooth',
        ),
        1 => array(
            0=> array(
                'name' => 'A1智能门禁机',
                'value' => 'A1',
            ),
            1=> array(
                'name' => 'A185智能门禁机',
                'value' => '61',
            ),
            2=> array(
                'name' => '西函智能门禁机',
                'value' => '31',
            )
        ),
        2 =>  array(
            0=> array(
                'name' => '10.1寸智能快递柜',
                'value' => 'Express_cabinet_101',
            ),
            1=> array(
                'name' => '21.5寸智能快递柜',
                'value' => 'Express_cabinet_215',
            ),
        ),
        3 => array(
            'name' => '停车管理',
            'value' => 'Parking_management'
        ),
        4 => array(
            'name' => '智能充电桩',
            'value' => 'Charging_pile'
        )
    );
    // 对应只能硬件区分  设备类型
    public $equipment_type_val = array(
        'Bluetooth' => '蓝牙门禁',
        'A1' => 'A1智能门禁机',
        'Express_cabinet_101' => '10.1寸智能快递柜',
        'Express_cabinet_215' => '21.5寸智能快递柜',
        'Parking_management' => '停车管理'
    );
    public $advert_type_arr = array(
        0 => '图片',
        1 => '视频',
        2 => '图片+视频',
    );
    // 视频支持格式
    public $video_arr = array('');
    // 图片和视频尺寸大小建议
    public $advert_file_tip = array(
        0 => array(
            array(
                'key' => 'Bluetooth',
                'tip' => '开门成功后展示,建议尺寸550*340'
            ),
            array(
                'key' => 'A1',
                'tip' => '图片大小不能超过10M'
            ),
            array(
                'key' => 'Express_cabinet_101',
                'tip' => '图片大小不能超过10M'
            ),
            array(
                'key' => 'Express_cabinet_215',
                'tip' => '图片大小不能超过10M'
            ),
            array(
                'key' => 'Parking_management',
                'tip' => '停车缴费展示,建议尺寸640*240'
            ),
            array(
                'key' => '61',
                'tip' => '图片大小不能超过10M,建议尺寸768*1366'
            ),
        ),
        1 => array(
            array(
                'key' => 'A1',
                'tip' => '视频大小不能超过10M',
                'max_size' => '10'
            ),
            array(
                'key' => 'Express_cabinet_101',
                'tip' => '视频大小不能超过10M',
                'max_size' => '10'
            ),
            array(
                'key' => 'Express_cabinet_215',
                'tip' => '视频大小不能超过10M',
                'max_size' => '10'
            ),
            array(
                'key' => '61',
                'tip' => '视频大小不能超过20M',
                'max_size' => '20'
            ),
        )
    );


    public function synchronous_hardware_advert($advert_id) {
        $advert_info = M('House_open_door_adver')->where(array('status'=>1,'id'=>$advert_id))->find();
        $village_list = M('Adver_village')->where(array('status'=>1,'adver_id'=>$advert_id))->getField('adver_id,village_id');
        if (empty($village_list)) {
            // 没有对应社区不做处理
            return true;
        }
        $d_house_face_device = M('House_face_device');
        $where_device = array();
        $where_device['device_status'] = 1;
        if (0==$advert_info['intelligent_hardware_type']) {
            // 蓝牙门禁不做任何处理
            return true;
        } elseif (1==$advert_info['intelligent_hardware_type']) {
            if ('A1'==$advert_info['equipment_type']) {
                $where_device['device_type'] = 1;
            }
        } elseif (2==$advert_info['intelligent_hardware_type']) {
            // 智能快递柜-暂不处理
            return true;
        }
        $village_arr = array();
        foreach ($village_list as $val) {
            $village_arr[] = $val['village_id'];
        }
        $where_device['village_id'] = array('in', $village_arr);
        $house_face_device_info = $d_house_face_device->where($where_device)->select();
        if ($house_face_device_info) {
            if (1==$advert_info['intelligent_hardware_type'] && 'A1'==$advert_info['equipment_type']) {
                import('@.ORG.rabbit_mq');
                $rabbit_mq = new rabbit_mq();
                $d_sync_advert = D('House_face_img');
                foreach ($house_face_device_info as $val) {
                    $d_sync_advert->sync_advert($val['device_id'],$val['device_sn'], $rabbit_mq);
                }
            }
        }
        return true;
    }
}