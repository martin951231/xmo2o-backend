<?php

class Config_customizationModel extends Model
{
    public function get_config()
    {
        $config = S('system_config_customization');
        if (empty($config)) {
            $_field = '`name`,`value`';

            $configs = $this->field($_field)->select();
            foreach ($configs as $key => $value) {
                $config[$value['name']] = $value['value'];

            }

            S('system_config_customization', $config, 120);
        }
        return $config;
    }

    public function getDhBrandJudge()
    {
        $device_da_hua_yun_rui_judge = $this->getDhYunRuiJudge();
        $device_da_h8900_judge = $this->getDhH8900Judge();
        if (!$device_da_hua_yun_rui_judge && !$device_da_h8900_judge) {
            return false;
        }
        return true;
    }

    public function getTaiShouBrandJudge()
    {
        $device_a1_face_judge = $this->getA1FaceDeviceJudge();
        $device_a2_face_judge = $this->getA2FaceDeviceJudge();
        if (!$device_a1_face_judge && !$device_a2_face_judge) {
            return false;
        }
        return true;
    }

    public function getZghlBrandJudge()
    {
        $device_d1_face_judge = $this->getD1FaceDeviceJudge();
        if (!$device_d1_face_judge) {
            return false;
        }
        return true;
    }

    public function getHasxBrandJudge()
    {
        $device_d3_face_judge = $this->getD3FaceDeviceJudge();
        if (!$device_d3_face_judge) {
            return false;
        }
        return true;
    }

    public function getLanNiuBrandJudge()
    {
        $device_d2_face_judge = $this->getD2FaceDeviceJudge();
        $device_d4_face_judge = $this->getD4FaceDeviceJudge();
        if (!$device_d2_face_judge && !$device_d4_face_judge) {
            return false;
        }
        return true;
    }

    public function getCloudWalkBrandJudge()
    {
        $device_d5_face_judge = $this->getD5FaceDeviceJudge();
        if (!$device_d5_face_judge) {
            return false;
        }
        return true;
    }

    public function getAnakeBrandJudge()
    {
        $device_anake_face_judge = $this->getAnakeFaceJudge();
        if (!$device_anake_face_judge) {
            return false;
        }
        return true;
    }

    public function getDuoPuBrandJudge()
    {
        $device_duopu_face_judge = $this->getDuoPuFaceJudge();
        if (!$device_duopu_face_judge) {
            return false;
        }
        return true;
    }

    public function getHaoBoXinBrandJudge()
    {
        $device_hao_bo_xin_face_judge = $this->getHaoBoXinFaceJudge();
        if (!$device_hao_bo_xin_face_judge) {
            return false;
        }
        return true;
    }

    public function getAllFaceJudge()
    {
        $judgeArr = S('system_config_customization_face_judge');
        if (empty($judgeArr) || !$judgeArr) {
            $judgeDhYunRui = $this->getDhYunRuiJudge();
            $judgeDhH8900 = $this->getDhH8900Judge();
            $judgeA1FaceDevice = $this->getA1FaceDeviceJudge();
            $judgeA2FaceDevice = $this->getA2FaceDeviceJudge();
            $judgeD1FaceDevice = $this->getD1FaceDeviceJudge();
            $judgeD2FaceDevice = $this->getD2FaceDeviceJudge();
            $judgeD3FaceDevice = $this->getD3FaceDeviceJudge();
            $judgeD4FaceDevice = $this->getD4FaceDeviceJudge();
            $judgeD5FaceDevice = $this->getD5FaceDeviceJudge();
            $judgeAnakeFace = $this->getAnakeFaceJudge();
            $judgeDuoPuFace = $this->getDuoPuFaceJudge();
            $judgeHaoBoXinFace = $this->getHaoBoXinFaceJudge();
            $judgeFaceCloud260Face = $this->getFaceCloud260Judge();
            $judgeFaceXiHanDoorSwitch = $this->getFaceXiHanDoorSwitch();
            $judgeArr = [
                'judgeDhYunRui' => $judgeDhYunRui,
                'judgeDhH8900' => $judgeDhH8900,
                'judgeA1FaceDevice' => $judgeA1FaceDevice,
                'judgeA2FaceDevice' => $judgeA2FaceDevice,
                'judgeD1FaceDevice' => $judgeD1FaceDevice,
                'judgeD2FaceDevice' => $judgeD2FaceDevice,
                'judgeD3FaceDevice' => $judgeD3FaceDevice,
                'judgeD4FaceDevice' => $judgeD4FaceDevice,
                'judgeD5FaceDevice' => $judgeD5FaceDevice,
                'judgeAnakeFace' => $judgeAnakeFace,
                'judgeDuoPuFace' => $judgeDuoPuFace,
                'judgeHaoBoXinFace' => $judgeHaoBoXinFace,
                'judgeFaceCloud260Face' => $judgeFaceCloud260Face,
                'judgeFaceXiHanDoorSwitch'=>$judgeFaceXiHanDoorSwitch,
            ];
            S('system_config_customization_face_judge', $judgeArr, 120);
        }
        return $judgeArr;
    }

    /**
     * 获取NB指纹锁是否支持
     * @return bool
     */
    public function getDhYunRuiNbJudge()
    {
        $config = $this->get_config();
        /** @var bool device_da_hua_yun_rui_nb_fingerprint_judge 大华设备-指纹锁是否支持 1支持  0或者没有这个配置项不支持 */
        $device_da_hua_yun_rui_nb_fingerprint_judge = isset($config['device_da_hua_yun_rui_nb_fingerprint']) && $config['device_da_hua_yun_rui_nb_fingerprint'] == 1 ? true : false;
        return $device_da_hua_yun_rui_nb_fingerprint_judge;
    }

    /**
     * 获取大华云睿是否支持
     * @return bool
     */
    public function getDhYunRuiJudge()
    {
        $config = $this->get_config();
        /** @var bool device_da_hua_yun_rui_judge 大华设备-大华云睿协议是否支持 1支持  0或者没有这个配置项不支持 */
        $device_da_hua_yun_rui_judge = isset($config['device_da_hua_yun_rui']) && $config['device_da_hua_yun_rui'] == 1 ? true : false;
        return $device_da_hua_yun_rui_judge;
    }

    /**
     * 获取大华 H8900 是否支持
     * @return bool
     */
    public function getDhH8900Judge()
    {
        $config = $this->get_config();
        /** @var bool device_da_h8900_judge 大华设备-H8900协议是否支持 1支持  0或者没有这个配置项不支持 */
        $device_da_h8900_judge = isset($config['device_da_hua_h8900']) && $config['device_da_hua_h8900'] == 1 ? true : false;
        return $device_da_h8900_judge;
    }

    public function getA1FaceDeviceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_a1_face_judge 泰首A1人脸设备是否支持  1支持  0或者没有这个配置项不支持 */
        $device_a1_face_judge = isset($config['device_a1_face']) && $config['device_a1_face'] == 1 ? true : false;
        return $device_a1_face_judge;
    }

    public function getA2FaceDeviceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_a2_face_judge 泰首A2人脸设备是否支持  1支持  0或者没有这个配置项不支持 */
        $device_a2_face_judge = isset($config['device_a2_face']) && $config['device_a2_face'] == 1 ? true : false;
        return $device_a2_face_judge;
    }

    public function getD1FaceDeviceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d1_face_judge 智国互联D1人脸设备是否支持  1支持  0或者没有这个配置项不支持 */
        $device_d1_face_judge = isset($config['device_d1_face']) && $config['device_d1_face'] == 1 ? true : false;
        return $device_d1_face_judge;
    }

    public function getD2FaceDeviceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d2_face_judge 蓝牛D2人脸设备是否支持  1支持  0或者没有这个配置项不支持 */
        $device_d2_face_judge = isset($config['device_d2_face']) && $config['device_d2_face'] == 1 ? true : false;
        return $device_d2_face_judge;
    }

    public function getD3FaceDeviceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d3_face_judge 华安视讯D3人脸设备是否支持  1支持  0或者没有这个配置项不支持 */
        $device_d3_face_judge = isset($config['device_d3_face']) && $config['device_d3_face'] == 1 ? true : false;
        return $device_d3_face_judge;
    }

    public function getD4FaceDeviceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d4_face_judge 蓝牛D4人脸设备是否支持  1支持  0或者没有这个配置项不支持 */
        $device_d4_face_judge = isset($config['device_d4_face']) && $config['device_d4_face'] == 1 ? true : false;
        return $device_d4_face_judge;
    }

    public function getD5FaceDeviceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d5_face_judge 智云丛科技D5 人脸设备是否支持  1支持  0或者没有这个配置项不支持 */
        $device_d5_face_judge = isset($config['device_d5_face']) && $config['device_d5_face'] == 1 ? true : false;
        return $device_d5_face_judge;
    }

    public function getAnakeFaceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_anake_face_judge 狄耐克人脸是否支持 1支持  0或者没有这个配置项不支持 */
        $device_anake_face_judge = isset($config['device_anake_face']) && $config['device_anake_face'] == 1 ? true : false;
        return $device_anake_face_judge;
    }

    public function getDuoPuFaceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_duopu_face_judge 朵普人脸是否支持 1支持  0或者没有这个配置项不支持 */
        $device_duopu_face_judge = isset($config['device_duopu_face']) && $config['device_duopu_face'] == 1 ? true : false;
        return $device_duopu_face_judge;
    }

    public function getHaoBoXinFaceJudge()
    {
        $config = $this->get_config();
        /** @var bool device_hao_bo_xin_face_judge 浩博鑫人脸是否支持 1支持  0或者没有这个配置项不支持 */
        $device_hao_bo_xin_face_judge = isset($config['device_hao_bo_xin_face']) && $config['device_hao_bo_xin_face'] == 1 ? true : false;
        return $device_hao_bo_xin_face_judge;
    }

    public function getD3ParkJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d3_park_judge 营山D3智慧停车系统  是否支持 1支持  0或者没有这个配置项不支持 */
        $device_d3_park_judge = isset($config['device_d3_park']) && $config['device_d3_park'] == 1 ? true : false;
        return $device_d3_park_judge;
    }


    public function getAnakeParkJudge()
    {
        $config = $this->get_config();
        /** @var bool device_anake_park_judge 狄耐克智慧停车D5是否支持 1支持  0或者没有这个配置项不支持 */
        $device_anake_park_judge = isset($config['device_anake_park']) && $config['device_anake_park'] == 1 ? true : false;
        return $device_anake_park_judge;
    }

    public function getD6ParkJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d6_park_judge D6智慧停车系统  是否支持 1支持  0或者没有这个配置项不支持 */
        $device_d6_park_judge = isset($config['device_d6_park']) && $config['device_d6_park'] == 1 ? true : false;
        return $device_d6_park_judge;
    }

    public function getD1ParkJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d1_park_judge D1智慧停车系统  是否支持 1支持  0或者没有这个配置项不支持 */
        $device_d1_park_judge = isset($config['device_d1_park']) && $config['device_d1_park'] == 1 ? true : false;
        return $device_d1_park_judge;
    }

    public function getD7ParkJudge()
    {
        $config = $this->get_config();
        /** @var bool device_d7_park_judge 亲邻D7智慧停车系统  是否支持 1支持  0或者没有这个配置项不支持 */
        $device_d7_park_judge = isset($config['device_d7_park']) && $config['device_d7_park'] == 1 ? true : false;
        return $device_d7_park_judge;
    }

    public function getAtcPileJudge()
    {
        $config = $this->get_config();
        /** @var bool device_atc_pile_judge 艾特充-充电桩  是否支持 1支持  0或者没有这个配置项不支持 */
        $device_atc_pile_judge = isset($config['device_atc_pile']) && $config['device_atc_pile'] == 1 ? true : false;
        return $device_atc_pile_judge;
    }

    public function getVoiceRecognitionOpenJudge()
    {
        $config = $this->get_config();
        /** 语音定制控制 */
        $voice_recognition_asr_open = isset($config['voice_recognition_asr_open']) && $config['voice_recognition_asr_open'] == 1 ? true : false;
        return $voice_recognition_asr_open;
    }

    public function getThirdImportDataSwitch()
    {
        $config = $this->get_config();
        /** @var bool third_import_data_switch 是否支持三方导入 */
        $third_import_data_switch_judge = isset($config['third_import_data_switch']) && $config['third_import_data_switch'] == 1 ? true : false;
        return $third_import_data_switch_judge;
    }


    public function getCarPileJudge()
    {
        $config = $this->get_config();
        /** @var bool device_car_pile 汽车充电桩是否支持 1支持  0或者没有这个配置项不支持 */
        $device_car_pile = isset($config['device_car_pile']) && $config['device_car_pile'] == 1 ? true : false;
        return $device_car_pile;
    }


    public function getVenueReservationSwitch()
    {
        $config = $this->get_config();
        /** @var bool venue_reservation 我的页面（我的消息、我的收藏） */
        $venue_reservation_switch_judge = isset($config['venue_reservation']) && $config['venue_reservation'] == 1 ? true : false;
        return $venue_reservation_switch_judge;
    }

    public function getchargeRuleFeesTypeByParkNumbers()
    {
        $config = $this->get_config();
        /** @var bool charge_rule_fees_type_by_park_numbers 收费标准支持计费模式(车位数量)  是否支持 1支持  0或者没有这个配置项不支持 */
        $charge_rule_fees_type_by_park_numbers_judge = isset($config['charge_rule_fees_type_by_park_numbers']) && $config['charge_rule_fees_type_by_park_numbers'] == 1 ? true : false;
        return $charge_rule_fees_type_by_park_numbers_judge;
    }


    public function getHzhouGrapefruitOrderJudge()
    {
        $config = $this->get_config();
        /** @var bool charge_rule_fees_type_by_park_numbers 收费标准支持计费模式(车位数量)  是否支持 1支持  0或者没有这个配置项不支持 */
        $hzhou_grapefruit_order = isset($config['hzhou_grapefruit_order']) && $config['hzhou_grapefruit_order'] == 1 ? true : false;
        return $hzhou_grapefruit_order;
    }

    public function getSqAeondeLightJiangsuCustomized()
    {
        $config = $this->get_config();
        /** @var bool sqaeondelightjiangsu_customized */
        $sqaeondelightjiangsu_customized = isset($config['sqaeondelightjiangsu_customized']) && $config['sqaeondelightjiangsu_customized'] == 1 ? true : false;
        return $sqaeondelightjiangsu_customized;
    }

    public function getHikFaceDeviceVisitorQRCode()
    {
        $config = $this->get_config();
        /** @var bool hik_face_device_vistor_qr_code 海康人脸访客户二维码是否支持  是否支持 1支持  0或者没有这个配置项不支持 */
        $hik_face_device_vistor_qr_code = isset($config['hik_face_device_vistor_qr_code']) && $config['hik_face_device_vistor_qr_code'] == 1 ? true : false;
        if ($hik_face_device_vistor_qr_code) {
            $house_a5_client_id = C('config.house_a5_client_id');
//            $HikCloudClientId   = C('config.HikCloudClientId');
//            if (!$house_a5_client_id && !$HikCloudClientId) {
//                $hik_face_device_vistor_qr_code = false;
//            }
            if (!$house_a5_client_id) {
                $hik_face_device_vistor_qr_code = false;
            }
        }
        return $hik_face_device_vistor_qr_code;
    }

    public function getAreaStreetReservationServiceSwitch()
    {
        $config = $this->get_config();
        /** @var bool area_street_reservation_service 街道/社区后台[预约服务]是否支持  1支持  0或者没有这个配置项不支持 */
        $result = isset($config['area_street_reservation_service']) && $config['area_street_reservation_service'] == 1 ? true : false;
        return $result;
    }

    public function getPropertyRobotJsdhwy()
    {
        $config = $this->get_config();
        /** @var  江苏德恒物业管理机器人 */
        $result = isset($config['property_robot_jsdhwy']) && $config['property_robot_jsdhwy'] == 1 ? true : false;
        return $result;
    }

    public function getFaceCloud260Switch()
    {
        $config = $this->get_config();
        /** @var bool face_cloud_260_switch 人脸门禁260云  1支持  0或者没有这个配置项不支持 */
        $result = isset($config['face_cloud_260_switch']) && $config['face_cloud_260_switch'] == 1 ? true : false;
        return $result;
    }

    public function getFaceCloud260Judge()
    {
        $device_face_cloud_260_judge = $this->getFaceCloud260Switch();
        if (!$device_face_cloud_260_judge) {
            return false;
        }
        return true;
    }

    public function getCbztssqCustom()
    {
        $config = $this->get_config();
        /** @var bool is_cbztssq_xcom   1支持  0或者没有这个配置项不支持 */
        $is_cbztssq_xcom = isset($config['is_cbztssq_xcom']) && $config['is_cbztssq_xcom'] == 1 ? 1 : 0;
        return $is_cbztssq_xcom;

    }

    public function getHavePropertyVirtual()
    {
        $config = $this->get_config();
        /** 添加物业时虚拟物业配置项   1支持  0或者没有这个配置项不支持  */
        $have_property_virtual = isset($config['have_property_virtual']) && $config['have_property_virtual'] == 1 ? 1 : 0;
        return $have_property_virtual;
    }
    
    public function getWapIndexJsdhwyUi()
    {
        $config = $this->get_config();
        /** 定制手机端新首页   1支持  0或者没有这个配置项不支持  */
        $wap_index_jsdhwy_ui = isset($config['wap_index_jsdhwy_ui']) && $config['wap_index_jsdhwy_ui'] == 1 ? 1 : 0;
        return $wap_index_jsdhwy_ui;
    }
    public function getPileChargeConsumePoint()
    {
        $config = $this->get_config();
        /** 定制手机端新首页   1支持  0或者没有这个配置项不支持  */
        $pile_charge_consume_point = isset($config['pile_charge_consume_point']) && $config['pile_charge_consume_point'] == 1 ? 1 : 0;
        return $pile_charge_consume_point;
    }
    // 客户万事通定制
    public function getWSXCustomizedOpen() {
        $config = $this->get_config();
        /** 客户万事通定制   1支持  0或者没有这个配置项不支持  */
        $jz_wsxdsj_net_customized_open = isset($config['jz_wsxdsj_net_customized_open']) && $config['jz_wsxdsj_net_customized_open'] == 1 ? true : false;
        return $jz_wsxdsj_net_customized_open;
    }
    
    // 客户完事通定制可公版部分开关
    public function getWSXCommonOpen() {
        $jz_wsxdsj_net_customized_open = $this->getWSXCustomizedOpen();
        $jz_wsxdsj_net_customized_open = true;
        return $jz_wsxdsj_net_customized_open;
    }

    public function getChinapopinOpen()
    {
        $config = $this->get_config();
        /** 对接省级智慧平安小区  */
        $chinapopin_open = isset($config['chinapopin_open']) && $config['chinapopin_open'] == 1 ? 1 : 0;
        return $chinapopin_open;
    }

    public function getSwitchQrCode()
    {
        $getHikFaceDeviceVisitorQRCode = D('Config_customization')->getHikFaceDeviceVisitorQRCode();
        $getFaceCloud260Judge = D('Config_customization')->getFaceCloud260Judge();
        if ($getHikFaceDeviceVisitorQRCode || $getFaceCloud260Judge) {
            return true;
        }
    }

    public function deviceQrCodeJudge($village_id)
    {
        $protocol = C('config.hikGatewayProtocol');
        $host = C('config.hikGatewayHost');
        $port = C('config.hikGatewayPort');
        $username = C('config.hikGatewayUserName');
        $password = C('config.hikGatewayPassword');
        if ($protocol && $host && $port && $username && $password) {
            return true;
        }
        $dbHouseDeviceGateway = M('House_device_gateway');
        $where = [
            'village_id' => $village_id, 'is_open'=>1
        ];
        $villageGateway = $dbHouseDeviceGateway->where($where)->find();
        $protocol = isset($villageGateway['protocol']) && $villageGateway['protocol'] ? $villageGateway['protocol'] : '';
        $host = isset($villageGateway['host']) && $villageGateway['host'] ? $villageGateway['host'] : '';
        $port = isset($villageGateway['port']) && $villageGateway['port'] ? $villageGateway['port'] : '';
        $username = isset($villageGateway['username']) && $villageGateway['username'] ? $villageGateway['username'] : '';
        $password = isset($villageGateway['password']) && $villageGateway['password'] ? $villageGateway['password'] : '';
        if ($protocol && $host && $port && $username && $password) {
            return true;
        }
        $house_a5_base_url = C('config.house_a5_base_url');
        $house_a5_client_id = C('config.house_a5_client_id');
        $house_a5_client_secret = C('config.house_a5_client_secret');
        if ($house_a5_base_url && $house_a5_client_id && $house_a5_client_secret) {
            $face_device_where=array('village_id'=>$village_id,'device_type'=>5,'device_status'=>1,'is_del'=>0);
            $one_a5_face_device=M('House_face_device')->where($face_device_where)->find();
            if(!empty($one_a5_face_device) && isset($one_a5_face_device['device_sn'])){
                return true;
            }
        }
        return false;
    }
    //小红马定制
    public function getIsAihorseCustomized() {
        $config = $this->get_config();
        /** 客户万事通定制   1支持  0或者没有这个配置项不支持  */
        $is_aihorse_customized = isset($config['is_aihorse_customized']) && $config['is_aihorse_customized'] == 1 ? true : false;
        return $is_aihorse_customized;
    }
    public function orderNoPayCanModifyMoney() {
        $config = $this->get_config();
        /** 修改待支付订单费用审核  */
        $isCanHave=0;
        if((isset($config['is_aihorse_customized']) && $config['is_aihorse_customized'] == 1 )|| (isset($config['jz_wsxdsj_net_customized_open']) && $config['jz_wsxdsj_net_customized_open'] == 1) ){
            $isCanHave=1;
        }
        if(isset($config['func_kaidalaicn_special']) && $config['func_kaidalaicn_special']==1){
            $isCanHave=1;
        }
        return $isCanHave;
    }
    public function getIntegralPool() {
        $config = $this->get_config();
        /** @var bool integral_pool 社区积分池（物业费未拖欠时可以使用房间所有人积分，拖欠时不允许使用积分）  1支持  0或者没有这个配置项不支持  */
        $result = isset($config['integral_pool']) && $config['integral_pool'] == 1 ? true : false;
        return $result;
    }

    public function getFaceXiHanDoorSwitch()
    {
        $config = $this->get_config();
        /** @var bool face_xi_hand_door_switch 西函门禁  1支持  0或者没有这个配置项不支持 */
        $result = isset($config['face_xi_hand_door_switch']) && $config['face_xi_hand_door_switch'] == 1 ? true : false;
        return $result;
    }
    
    public function getVr720yunCustomized()
    {
        /** VR实景跳转720vr全景的链接 */
        $config = $this->get_config();
        return isset($config['is_vr_720yun']) && $config['is_vr_720yun'] == 1 ? true : false;
    }


    public function get_zhsq_hs_zhiyunkjt_customized()
    {
        $config = $this->get_config();
        /** @var bool zhsq_hs_zhiyunkjt_customized 定制控制字段 */
        $result = isset($config['zhsq_hs_zhiyunkjt_customized']) && $config['zhsq_hs_zhiyunkjt_customized'] == 1 ? true : false;
        return $result;
    }
    public function getYuanJino2oCustomized()
    {
        /** www.yuanjino2o.com定制 */
        $config = $this->get_config();
        return isset($config['yuanjino2o_customized_func']) && $config['yuanjino2o_customized_func'] == 1 ? 1 : 0;
    }
    public function getGuessYouLikeGoodsCustomized()
    {
        $config = $this->get_config();
        /** 猜你喜欢的商业商品   1支持  0或者没有这个配置项不支持  */
        $guess_you_like_goods = isset($config['guess_you_like_goods']) && $config['guess_you_like_goods'] == 1 ? 1 : 0;
        return $guess_you_like_goods;
    }

    public function getShowO2oRecord()
    {
        $config = $this->get_config();
        /** 显示商业的用户中心的我的记录  1支持  0或者没有这个配置项不支持  */
        $housemy_o2o_record = isset($config['housemy_o2o_record']) && $config['housemy_o2o_record'] == 1 ? 1 : 0;
        return $housemy_o2o_record;

    }
    
    public function getCustomizedUserOpenDoorDay()
    {
        /** www.Zyrealnet.com */
        $config = $this->get_config();
        return isset($config['user_open_door_day']) && $config['user_open_door_day'] == 1 ? 1 : 0;
    }
    

    public function getfuncKaidalaicnSpecial()
    {
        /** new.kaidalai.cn */
        $config = $this->get_config();
        return isset($config['func_kaidalaicn_special']) && $config['func_kaidalaicn_special'] == 1 ? 1 : 0;
    }
    public function getQrcodeRecordExportDetails(){
        $config = $this->get_config();
        return isset($config['qrcode_record_export_details']) && $config['qrcode_record_export_details'] == 1 ? 1 : 0;

    }

    public function getfuncYougejianetSpecial()
    {
        /** zhihuishequ.yougejia.net定制 */
        $config = $this->get_config();
        $is_func_yougejianet_special = isset($config['func_yougejianet_special']) && $config['func_yougejianet_special'] == 1 ? 1 : 0;
        return $is_func_yougejianet_special;
    }
}
?>
