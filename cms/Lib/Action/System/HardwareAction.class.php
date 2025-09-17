<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      代码功能描述
 */


class HardwareAction extends BaseAction
{
    public function faceHardware()
    {
        $condition_house_village = [];
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'device_sn') {
                $condition_house_village['device_sn'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'village_id') {
                $condition_house_village['village_id'] = (int)$_GET['keyword'];
            }
            if ($_GET['searchtype'] == 'device_name') {
                $condition_house_village['device_name'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
        }
        $condition_house_village['is_del'] = 0;
        $database_house_property = D('House_face_device');
        $count_village = $database_house_property->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, 20);
        $field = "`device_id`,`device_name`,`device_type`,`device_sn`,`village_id`,`device_status`,`device_direction`,`notify_time`,`device_brand`,`device_direction`";
        $property_list = $database_house_property->field($field)->where($condition_house_village)->order('`notify_time` desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $deviceTypeList = D('House_face_device')->device_type_info;
        
        if ($property_list) {
           
            $deviceBrandList = D('HardwareBrand')->getBrand(1);
            $House_village = D('House_village');
            foreach ($property_list as &$val) {
                $val['villageName'] = $House_village->getVillageNameById($val['village_id']);
                $val['notify_date'] = '-';
                if ($val['notify_time'] > 0){
                    $val['notify_date'] = date('Y-m-d H:i:s',$val['notify_time']);
                }
                
                $val['device_type_name'] = '其他';
                $val['device_brand_name'] = '-';
               foreach ($deviceTypeList as $item){
                   if ((int)$item['device_type'] === (int)$val['device_type']){
                       $val['device_type_name'] = $item['name'];
                   } 
               }
                foreach ($deviceBrandList as $banitem){
                    if ($banitem['key'] == $val['device_brand']){
                        $val['device_brand_name'] = $banitem['title'];
                    }
                }
                
            }
        }
        
        $this->assign('hardwareList', $property_list);
        $this->assign('deviceTypeList', $deviceTypeList);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        
        $this->display();
    }

    public function bluetoothHardware()
    {
        $condition_house_village = [];
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'device_sn') {
                $condition_house_village['device_sn'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'village_id') {
                $condition_house_village['village_id'] = (int)$_GET['keyword'];
            }
            if ($_GET['searchtype'] == 'door_name') {
                $condition_house_village['door_name'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
        }

        $database_house_property = D('House_village_door');
        $count_village = $database_house_property->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, 20);
        $field = "`door_id`,`village_id`,`door_device_id`,`door_name`,`door_status`,`all_status`,`last_open_time`";
        $property_list = $database_house_property->field($field)->where($condition_house_village)->order('`last_open_time` desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        if ($property_list) {
            $House_village = D('House_village');
             foreach ($property_list as &$val) {
                 $val['villageName'] = $House_village->getVillageNameById($val['village_id']);
                $val['last_open_date'] = '-';
                if ($val['last_open_time'] > 0){
                    $val['last_open_date'] = date('Y-m-d H:i:s',$val['last_open_time']);
                }
             }
        }

        $this->assign('hardwareList', $property_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        $this->display();
    }

    public function chargeHardware()
    {
        $condition_house_village = [];
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'equipment_sn') {
                $condition_house_village['equipment_sn'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'equipment_serial') {
                $condition_house_village['equipment_serial'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'village_id') {
                $condition_house_village['village_id'] = (int)$_GET['keyword'];
            }
            if ($_GET['searchtype'] == 'equipment_name') {
                $condition_house_village['equipment_name'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
        }
        $condition_house_village['is_del'] = 1;
        
        $database_house_property = D('House_village_pile_equipment');
        $count_village = $database_house_property->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, 20);
        $field = "`id`,`equipment_name`,`equipment_type`,`equipment_num`,`village_id`,`equipment_serial`,`last_heart_time`,`equipment_sn`,`status`,`type`";
        $property_list = $database_house_property->field($field)->where($condition_house_village)->order('`last_heart_time` desc')->limit($p->firstRow . ',' . $p->listRows)->select();
       
         if ($property_list) {
            $House_village = D('House_village');
                $base_url = $House_village->base_url;
                $site_url = C('config.site_url');
                $time = time() - 300;
                foreach ($property_list as  &$v) {
                    $v['villageName'] = $House_village->getVillageNameById($v['village_id']);
                    if ($v['type'] != 1) {
                        $url = $site_url . $base_url . 'pages/village/smartCharge/smartCharge?type=' . $v['type'] . '&qcodeNum=' . $v['equipment_serial'];
                        $img_url = $site_url . '/shequ.php?g=House&c=Charging&a=get_own_qrcode&qrCon=' . urlencode(htmlspecialchars_decode($url));
                    } else {
                        $img_url = '';
                    }
                    $v['qrcode'] = $img_url;
                    if (isset($v['last_heart_time']) && $v['last_heart_time'] < $time) {
                        $v['statusHeart'] = 44;
                        if($v['last_heart_time'] > 1000) {
                            $v['statusHeartTime'] = date('Y-m-d H:i:s',$v['last_heart_time']);
                        }else{
                            $v['statusHeartTime'] = '-';
                        }
                    } elseif (isset($v['last_heart_time']) && $v['last_heart_time']>1) {
                        $v['statusHeart'] = 1;
                        $v['statusHeartTime'] = date('Y-m-d H:i:s',$v['last_heart_time']);
                    }
                    $v['last_heart_time'] = (isset($v['last_heart_time']) && $v['last_heart_time']>1) ? date('Y-m-d H:i:s',$v['last_heart_time']) : '--';
                }
        }

        $this->assign('hardwareList', $property_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        $this->display();
    }
    
    public function videoHardware()
    {
        $condition_house_village = [];
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'camera_sn') {
                $condition_house_village['camera_sn'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'camera_name') {
                $condition_house_village['camera_name'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'village_id') {
                $condition_house_village['village_id'] = (int)$_GET['keyword'];
            }
        }
        $condition_house_village['camera_status'] = ['neq', 4];

        $database_house_property = D('House_camera_device');
        $count_village = $database_house_property->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, 20);
        $field = "`camera_id`,`camera_name`,`camera_sn`,`camera_status`,`village_id`,`brand_name`,`product_name`,`product_model`,`device_type`,`reason`, `thirdProtocol`";
        $property_list = $database_house_property->field($field)->where($condition_house_village)->limit($p->firstRow . ',' . $p->listRows)->order('camera_id DESC')->select();
        if ($property_list) {
            $House_village = D('House_village');
            $thirdProtocolArr = D('House_face_device')->getThirdProtocolArr();
            foreach ($property_list as &$val) {
                $val['villageName'] = $House_village->getVillageNameById($val['village_id']);
                $thirdProtocol = isset($val['thirdProtocol']) ? intval($val['thirdProtocol']) : 0;
                if (isset($thirdProtocolArr[$thirdProtocol]) && isset($thirdProtocolArr[$thirdProtocol]['thirdTitle'])) {
                    $val['thirdProtocolText'] = $thirdProtocolArr[$thirdProtocol]['thirdTitle'];
                    $val['brand_text'] = $thirdProtocolArr[$thirdProtocol]['brand'];
                } else {
                    $val['thirdProtocolText'] = '-';
                    $val['brand_text'] = '-';
                }
                if (isset($val['camera_status'])&&$val['camera_status']==0) {
                    $val['camera_status'] = '在线';
                } else {
                    $val['camera_status'] = '离线';
                }
            }
        }

        $this->assign('hardwareList', $property_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        $this->display();
    }
    
    public function carHardware()
    {
        $condition_house_village = [];
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'passage_name') {
                $condition_house_village['passage_name'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'village_id') {
                $condition_house_village['village_id'] = (int)$_GET['keyword'];
            }
            if ($_GET['searchtype'] == 'channel_number') {
                $condition_house_village['channel_number'] = (int)$_GET['keyword'];
            }
            if ($_GET['searchtype'] == 'device_number') {
                $condition_house_village['device_number'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
        }

        $database_house_property = D('Park_passage');
        $count_village = $database_house_property->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, 20);
//        $field = "`id`,`village_id`,`park_sys_type`,`passage_name`,`status`,`device_number`,`channel_number`,`passage_direction`,`park_type`,`area_type`,`last_heart_time`,`garage_id`";
        $field = "`id`,`village_id`,`park_sys_type`,`passage_name`,`status`,`device_number`,`channel_number`,`passage_direction`,`park_type`,`area_type`,`last_heart_time`";
        $property_list = $database_house_property->field($field)->where($condition_house_village)->order('`last_heart_time` desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        if ($property_list) {
            $House_village = D('House_village');
            $House_village_parking_garage = D('House_village_parking_garage');
            $House_village_public_area = D('House_village_public_area');
            foreach ($property_list as &$val) {
                $val['villageName'] = $House_village->getVillageNameById($val['village_id']);

                if ($val['passage_direction']==1){
                    $val['passage_direction_txt']='入口';
                }elseif($val['passage_direction']==2){
                    $val['passage_direction_txt']='出入口';
                }else{
                    $val['passage_direction_txt']='出口';
                }

                if ($val['area_type']==2){
//                    $area_name= $House_village_parking_garage->where(['garage_id'=>(int)$val['garage_id']])->field(['garage_id','garage_num','garage_position'])->find();
                    $area_name= $House_village_parking_garage->where(['garage_id'=>(int)$val['passage_area']])->field(['garage_id','garage_num','garage_position'])->find();
                    $val['area_name']=$area_name['garage_num'];
                } elseif ($val['area_type']==1){
                    $area_name=$House_village_public_area->where(['public_area_id'=>(int)$val['passage_area']])->field(['public_area_id','public_area_name'])->find();
                    $area_name=$House_village_public_area->where(['public_area_id'=>(int)$val['passage_area']])->field(['public_area_id','public_area_name'])->find();
                    $val['area_name']=$area_name['public_area_name'];
                }else{
                    $val['area_name']='-';
                }
                if ($val['status']==1){
                    $val['status_txt']='开启';
                }else{
                    $val['status_txt']='关闭';
                }
                
                if ($val['last_heart_time'] > 1){
                    $val['last_heart_time']=date('Y-m-d H:i:s',$val['last_heart_time']);
                }else{
                    $val['last_heart_time'] = '-';
                }
            }
        }
 
        $this->assign('hardwareList', $property_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function alarmHardware()
    {
        $condition = [];
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'device_id') {
                $condition['device_id'] = (int)$_GET['keyword'];
            }
            if ($_GET['searchtype'] == 'device_name') {
                $condition['device_name'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'device_serial') {
                $condition['device_serial'] = array('like', '%' . trim($_GET['keyword']) . '%');
            }
            if ($_GET['searchtype'] == 'village_id') {
                $condition['village_id'] = (int)$_GET['keyword'];
            }
        }
        $condition['is_del'] = 0;
        $database_house_device = D('House_device');
        $count = $database_house_device->where($condition)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $field = "`device_id`,`village_id`,`device_type`,`device_name`,`device_serial`, `third_protocol`,`device_status`,`device_model`,`interaction_time`,`cloud_status`,`cloud_reason`";
        $list = $database_house_device->field($field)->where($condition)->order('`interaction_time` desc, `device_id` desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        if ($list) {
            $House_village = D('House_village');
            $thirdProtocolArr = D('House_face_device')->getThirdProtocolArr();
            foreach ($list as &$val) {
                $val['villageName'] = $House_village->getVillageNameById($val['village_id']);
                $thirdProtocol = isset($val['third_protocol']) ? intval($val['third_protocol']) : 0;
                if (isset($thirdProtocolArr[$thirdProtocol]) && isset($thirdProtocolArr[$thirdProtocol]['thirdTitle'])) {
                    $val['thirdProtocolText'] = $thirdProtocolArr[$thirdProtocol]['thirdTitle'];
                    $val['brand_text'] = $thirdProtocolArr[$thirdProtocol]['brand'];
                } else {
                    $val['thirdProtocolText'] = '-';
                    $val['brand_text'] = '-';
                }
                // 0-未知  1-正常 2-停止/待机
                $device_status = isset($val['device_status']) ? intval($val['device_status']) : 0;
                if ($device_status == 0) {
                    $val['device_status_text'] = '未知';
                    $val['device_status_color'] = 'gray';
                } elseif ($device_status == 1) {
                    $val['device_status_text'] = '正常';
                    $val['device_status_color'] = 'green';
                } elseif ($device_status == 3) {
                    $val['device_status_text'] = '升级中';
                    $val['device_status_color'] = 'black';
                } else {
                    $val['camera_status'] = '停止/待机';
                    $val['device_status_color'] = 'red';
                }
                if (isset($val['interaction_time']) && $val['interaction_time']>1) {
                    $val['interaction_time_text'] = date('Y-m-d H:i:s',$val['interaction_time']);
                } else {
                    $val['interaction_time_text'] = '-';
                }
                // 	同步状态 0-未知  1-同步中 2-同步成功 3-同步失败
                $cloud_status = isset($val['cloud_status']) ? intval($val['cloud_status']) : 0;
                $cloud_reason = isset($val['cloud_reason']) ? trim($val['cloud_reason']) : '';
                if ($cloud_status == 0) {
                    $val['cloud_text'] = '未同步';
                } elseif ($cloud_status == 1) {
                    $val['cloud_text'] = '同步中';
                } elseif ($cloud_status == 2) {
                    $val['cloud_text'] = '同步成功';
                } else {
                    $val['cloud_text'] = '同步失败';
                    $cloud_reason && $val['cloud_text'] .= "({$cloud_reason})";
                }
            }
        }
        $this->assign('list', $list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        $this->display();
    }
}