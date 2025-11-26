<?php
/**
 * 客户定制开关配置
 * Author: hengtingmei
 * Date Time: 2021/11/04 09:01
 */

namespace app\common\model\service\config;

use app\common\model\db\ConfigCustomization;
use think\facade\Cache;

class ConfigCustomizationService {
    public $configObj = null;
    public function __construct()
    {
        $this->configObj = new ConfigCustomization();
    }

    /**
     * 返回正常显示的菜单
     * @return array
     */
    public function getConfigData() {
        // 获得所有菜单
        $returnArr = Cache::get('o2ocustomization');//这里改名的原因是因为系统其他地方用到了这个config名字，所以跟这里冲突了，但又没查出哪里用到
        if(empty($returnArr)){
            $field = 'name,value';
            $configList = $this->getSome([],$field);
            if(!$configList) {
                return [];
            }

            foreach($configList as $key=>$value){
                $returnArr[$value['name']] = $value['value'];
            }
			
            Cache::set('o2ocustomization', $returnArr);
        }
        return $returnArr;
    }

    /**
     * 返回正常显示的菜单
     * @param $id
     * @return array
     */
    public function getSome($where = [],$field=true) {
        $list = $this->configObj->getSome($where,$field);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 查询一条数据
     * @param array $where
     * @return mixed
     */
    public function getOne($where = []) {
        $data = $this->configObj->getOne($where);
        return $data ? $data->toArray() : [];
    }

    /**
     * 查询一条数据 并返回值
     * @param string $field 获得配置项名称
     * @return mixed
     */
    public function getOneField($field) {
        $return = '';
        $where = [
            'name' => $field
        ];
        $data = $this->getOne($where);
        if($data){
            $return = $data['value'];
        }
        return $return;
    }

    public function getD1ParkJudge() {
        $config = $this->getConfigData();
        /** @var bool device_d1_park_judge D1智慧停车系统  是否支持 1支持  0或者没有这个配置项不支持 */
        $device_d1_park_judge = isset($config['device_d1_park']) && $config['device_d1_park'] == 1 ? true : false;
        return $device_d1_park_judge;
    }

    public function getD3ParkJudge() {
        $config = $this->getConfigData();
        /** @var bool device_d3_park_judge 营山D3智慧停车系统  是否支持 1支持  0或者没有这个配置项不支持 */
        $device_d3_park_judge = isset($config['device_d3_park']) && $config['device_d3_park'] == 1 ? true : false;
        return $device_d3_park_judge;
    }

    public function getAnakeParkJudge() {
        $config = $this->getConfigData();
        /** @var bool device_anake_park_judge 狄耐克智慧停车D5是否支持 1支持  0或者没有这个配置项不支持 */
        $device_anake_park_judge = isset($config['device_anake_park']) && $config['device_anake_park'] == 1 ? true : false;
        return $device_anake_park_judge;
    }

    public function getVoiceRecognitionOpenJudge()
    {
        $config = $this->getConfigData();
        /** 语音定制控制 */
        $voice_recognition_asr_open = isset($config['voice_recognition_asr_open']) && $config['voice_recognition_asr_open'] == 1 ? true : false;
        return $voice_recognition_asr_open;
    }


    public function getCarPileJudge() {
        $config = $this->getConfigData();
        /** @var bool device_car_pile 汽车充电桩是否支持 1支持  0或者没有这个配置项不支持 */
        $device_car_pile = isset($config['device_car_pile']) && $config['device_car_pile'] == 1 ? true : false;
        return $device_car_pile;
    }

    
    /*
     * zairizhao.cn家判断
     */
    

    public function getZairizhaoCnJudge()
    {
	    $config = $this->getConfigData();
	    /** @var bool zairizhao.cn配置 */
	    $is_life_tools = isset($config['life_tools']) && $config['life_tools'] == 1 ? 1 : 0;

	    return $is_life_tools;
    }

    public function getThirdImportDataSwitch() {
        $config = $this->getConfigData();
        /** @var bool third_import_data_switch 是否支持三方导入 */
        $third_import_data_switch_judge = isset($config['third_import_data_switch']) && $config['third_import_data_switch'] == 1 ? true : false;
        return $third_import_data_switch_judge;
    }

    
    public function getchargeRuleFeesTypeByParkNumbers() {
        $config = $this->getConfigData();
        /** @var bool charge_rule_fees_type_by_park_numbers 收费标准支持计费模式(车位数量)  是否支持 1支持  0或者没有这个配置项不支持 */
        $charge_rule_fees_type_by_park_numbers_judge = isset($config['charge_rule_fees_type_by_park_numbers']) && $config['charge_rule_fees_type_by_park_numbers'] == 1 ? true : false;
        return $charge_rule_fees_type_by_park_numbers_judge;
    }
    
    public function getHikFaceDeviceVisitorQRCode() {
        $config = $this->getConfigData();
        /** @var bool hik_face_device_vistor_qr_code 海康人脸访客户二维码是否支持  是否支持 1支持  0或者没有这个配置项不支持 */
        $hik_face_device_vistor_qr_code = isset($config['hik_face_device_vistor_qr_code']) && $config['hik_face_device_vistor_qr_code'] == 1 ? true : false;
        if ($hik_face_device_vistor_qr_code) {
            $house_a5_client_id = cfg('house_a5_client_id');
//            $HikCloudClientId   = cfg('HikCloudClientId');
//            if (!$house_a5_client_id && !$HikCloudClientId) {
//                $hik_face_device_vistor_qr_code = false;
//            }
            if (!$house_a5_client_id) {
                $hik_face_device_vistor_qr_code = false;
            }
        }
        return $hik_face_device_vistor_qr_code;
    }
    
    public function getAreaStreetReservationServiceSwitch() {
        $config = $this->getConfigData();
        /** @var bool area_street_reservation_service 街道/社区后台[预约服务]是否支持  1支持  0或者没有这个配置项不支持 */
        $result = isset($config['area_street_reservation_service']) && $config['area_street_reservation_service'] == 1 ? true : false;
        return $result;
    }


    public function getHzhouGrapefruitOrderJudge($is_force=false)
    {
        $config = $this->getConfigData();
        /** @var int oa.8003.cn杭州西柚记 配置 */
        $hzhou_grapefruit_order = isset($config['hzhou_grapefruit_order']) && $config['hzhou_grapefruit_order'] == 1 ? 1 : 0;
        if($is_force){
            //走队列调用时 强制查询
            $hzhou_grapefruit_order=0;
            $whereArr=array();
            $whereArr[]=['name','=','hzhou_grapefruit_order'];
            $oneTmp= $this->configObj->getOne($whereArr,'value');
            if($oneTmp && !$oneTmp->isEmpty()){
                $hzhou_grapefruit_order = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $hzhou_grapefruit_order;
    }
    public function getAnakeFaceJudge() {
        $config = $this->getConfigData();
        /** @var bool device_anake_face_judge 狄耐克人脸是否支持 1支持  0或者没有这个配置项不支持 */
        $device_anake_face_judge = isset($config['device_anake_face']) && $config['device_anake_face'] == 1 ? true : false;
        return $device_anake_face_judge;
    }

    public function getAnakeBrandJudge() {
        $device_anake_face_judge = $this->getAnakeFaceJudge();
        if (!$device_anake_face_judge) {
            return false;
        }
        return true;
    }

    public function getCbztssqCustom() {
        $config = $this->getConfigData();
        /** @var bool is_cbztssq_xcom   1支持  0或者没有这个配置项不支持  */
        $is_cbztssq_xcom = isset($config['is_cbztssq_xcom']) && $config['is_cbztssq_xcom'] == 1 ? 1 : 0;
        return $is_cbztssq_xcom;

    }

    public function getHangLanShequCustom() {
        $config = $this->getConfigData();
        /** @var bool is_cbztssq_xcom   1支持  0或者没有这个配置项不支持  */
        $hanglan_shequ_com = isset($config['hanglan_shequ_com']) && $config['hanglan_shequ_com'] == 1 ? 1 : 0;
        return $hanglan_shequ_com;
    }

    public function getHavePropertyVirtual() {
        $config = $this->getConfigData();
        /** 添加物业时虚拟物业配置项   1支持  0或者没有这个配置项不支持  */
        $have_property_virtual = isset($config['have_property_virtual']) && $config['have_property_virtual'] == 1 ? 1 : 0;
        return $have_property_virtual;
    }



    public function getWapIndexJsdhwyUi()
    {
        $config = $this->getConfigData();
        /** 定制手机端新首页   1支持  0或者没有这个配置项不支持  */
        $wap_index_jsdhwy_ui = isset($config['wap_index_jsdhwy_ui']) && $config['wap_index_jsdhwy_ui'] == 1 ? 1 : 0;
        return $wap_index_jsdhwy_ui;
    }
    public function getPileChargeConsumePoint($is_force=false)
    {
        $config = $this->getConfigData();
        /** 定制手机端新首页   1支持  0或者没有这个配置项不支持  */
        $pile_charge_consume_point = isset($config['pile_charge_consume_point']) && $config['pile_charge_consume_point'] == 1 ? 1 : 0;
        if($is_force){
            //走队列调用时 强制查询
            $pile_charge_consume_point=0;
            $whereArr=array();
            $whereArr[]=['name','=','pile_charge_consume_point'];
            $oneTmp= $this->configObj->getOne($whereArr,'value');
            if($oneTmp && !$oneTmp->isEmpty()){
                $pile_charge_consume_point = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $pile_charge_consume_point;
    }
    public function getWSXCustomizedOpen($funcType = '')
    {
        $config = $this->getConfigData();
        /** 客户万事通定制   1支持  0或者没有这个配置项不支持  */
        $jz_wsxdsj_net_customized_open = isset($config['jz_wsxdsj_net_customized_open']) && $config['jz_wsxdsj_net_customized_open'] == 1 ? true : false;
        if ($funcType == 'organizationWorker') {
            //$jz_wsxdsj_net_customized_open=true;
        }
        return $jz_wsxdsj_net_customized_open;
    }

    public function getWSXCustomizedOpenForce()
    {
        //走队列调用时 强制查询
        $jz_wsxdsj_net_customized_open = 0;
        $whereArr = array();
        $whereArr[] = ['name', '=', 'jz_wsxdsj_net_customized_open'];
        $oneTmp = $this->configObj->getOne($whereArr, 'value');
        if ($oneTmp && !$oneTmp->isEmpty()) {
            $jz_wsxdsj_net_customized_open = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
        }
        return $jz_wsxdsj_net_customized_open;
    }
    // 客户完事通定制可公版部分开关
    public function getWSXCommonOpen()
    {
        $jz_wsxdsj_net_customized_open = $this->getWSXCustomizedOpen();
        $jz_wsxdsj_net_customized_open = true;
        return $jz_wsxdsj_net_customized_open;
    }
    
    public function getCityCountyManageFuncJudge($is_force=false)
    {
        $config = $this->getConfigData();
        /** @var int 城市运营 配置 */
        $city_county_manage_func = isset($config['city_county_manage_func']) && $config['city_county_manage_func'] == 1 ? 1 : 0;
        if($is_force){
            //走队列调用时 强制查询
            $city_county_manage_func=0;
            $whereArr=array();
            $whereArr[]=['name','=','city_county_manage_func'];
            $oneTmp= $this->configObj->getOne($whereArr,'value');
            if($oneTmp && !$oneTmp->isEmpty()){
                $city_county_manage_func = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $city_county_manage_func;
    }
    
    public function getFaceCloud260Switch() {
        $config = $this->getConfigData();
        /** @var bool face_cloud_260_switch 人脸门禁260云  1支持  0或者没有这个配置项不支持  */
        $result = isset($config['face_cloud_260_switch']) && $config['face_cloud_260_switch'] == 1 ? true : false;
        return $result;
    }

    public function getFaceCloud260Judge() {
        $device_face_cloud_260_judge = $this->getFaceCloud260Switch();
        if (!$device_face_cloud_260_judge) {
            return false;
        }
        return true;
    }

    public function getIntegralPool() {
        $config = $this->getConfigData();
        /** @var bool integral_pool 社区积分池（物业费未拖欠时可以使用房间所有人积分，拖欠时不允许使用积分）  1支持  0或者没有这个配置项不支持  */
        $result = isset($config['integral_pool']) && $config['integral_pool'] == 1 ? true : false;
        return $result;
    }

    public function getAihorseCustomizedJudge($is_force=false)
    {
        $config = $this->getConfigData();
        /**小红马 **/
        $is_aihorse_customized = isset($config['is_aihorse_customized']) && $config['is_aihorse_customized'] == 1 ? 1 : 0;
        if($is_force){
            //走队列调用时 强制查询
            $is_aihorse_customized=0;
            $whereArr=array();
            $whereArr[]=['name','=','is_aihorse_customized'];
            $oneTmp= $this->configObj->getOne($whereArr,'value');
            if($oneTmp && !$oneTmp->isEmpty()){
                $is_aihorse_customized = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $is_aihorse_customized;
    }
    public function getSqAeondeLightJiangsuCustomized() {
        $config = $this->getConfigData();
        /** @var bool sqaeondelightjiangsu_customized */
        $sqaeondelightjiangsu_customized = isset($config['sqaeondelightjiangsu_customized']) && $config['sqaeondelightjiangsu_customized'] == 1 ? true : false;
        return $sqaeondelightjiangsu_customized;
        
    }
    public function getSetDiscountPrecision()
    {
        $config = $this->getConfigData();
        /**社区预缴折扣率小数位数是否为2 **/
        $set_discount_precision = isset($config['set_discount_precision']) && $config['set_discount_precision'] == 1 ? 1 : 0;
        return $set_discount_precision;
    }
    public function getChinapopinOpen() {
        $config = $this->getConfigData();
        /** 对接省级智慧平安小区  */
        $result = isset($config['chinapopin_open']) && $config['chinapopin_open'] == 1 ? true : false;
        return $result;
    }
    
    public function getFaceXiHanDoorSwitch()
    {
        $config = $this->getConfigData();
        /** @var bool face_xi_hand_door_switch 西函门禁  1支持  0或者没有这个配置项不支持 */
        $result = isset($config['face_xi_hand_door_switch']) && $config['face_xi_hand_door_switch'] == 1 ? true : false;
        return $result;
    }
    
    public function getVr720yunCustomized()
    {
        /** VR实景跳转720vr全景的链接 */
        $config = $this->getConfigData();
        return isset($config['is_vr_720yun']) && $config['is_vr_720yun'] == 1 ? true : false;
    }
    
    public function getYuanJino2oCustomized()
    {
        /** www.yuanjino2o.com定制 */
        $config = $this->getConfigData();
        return isset($config['yuanjino2o_customized_func']) && $config['yuanjino2o_customized_func'] == 1 ? 1 : 0;
    }
    
    public function getFuncAqh2025cnSpecial()
    {
        /** qh2025.cn定制 */
        $config = $this->getConfigData();
        return isset($config['func_aqh2025cn_special']) && $config['func_aqh2025cn_special'] == 1 ? 1 : 0;
    }
    
    public function getYuyanAihorsenetSpecialJudge($is_force=false)
    {
        $config = $this->getConfigData();
        /**小红马 **/
        $is_yuyanaihorsenet_customized = isset($config['func_yuyanaihorsenet_special']) && $config['func_yuyanaihorsenet_special'] == 1 ? 1 : 0;
        if ($is_force) {
            //走队列调用时 强制查询
            $is_yuyanaihorsenet_customized = 0;
            $whereArr = array();
            $whereArr[] = ['name', '=', 'func_yuyanaihorsenet_special'];
            $oneTmp = $this->configObj->getOne($whereArr, 'value');
            if ($oneTmp && !$oneTmp->isEmpty()) {
                $is_yuyanaihorsenet_customized = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $is_yuyanaihorsenet_customized;
    }

    public function getfuncEfamily365Special($is_force = false)
    {
        /** efamily365.cn定制 */
        $config = $this->getConfigData();
        $is_func_efamily365_special = isset($config['func_efamily365_special']) && $config['func_efamily365_special'] == 1 ? 1 : 0;
        if ($is_force) {
            //走队列调用时 强制查询
            $is_func_efamily365_special = 0;
            $whereArr = array();
            $whereArr[] = ['name', '=', 'func_efamily365_special'];
            $oneTmp = $this->configObj->getOne($whereArr, 'value');
            if ($oneTmp && !$oneTmp->isEmpty()) {
                $is_func_efamily365_special = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $is_func_efamily365_special;

    }


    public function getfuncKaidalaicnSpecial($is_force = false)
    {
        /** new.kaidalai.cn定制 */
        $config = $this->getConfigData();
        $is_func_kaidalaicn_special = isset($config['func_kaidalaicn_special']) && $config['func_kaidalaicn_special'] == 1 ? 1 : 0;
        if ($is_force) {
            //走队列调用时 强制查询
            $is_func_kaidalaicn_special = 0;
            $whereArr = array();
            $whereArr[] = ['name', '=', 'func_kaidalaicn_special'];
            $oneTmp = $this->configObj->getOne($whereArr, 'value');
            if ($oneTmp && !$oneTmp->isEmpty()) {
                $is_func_kaidalaicn_special = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $is_func_kaidalaicn_special;
    }
    public function getfuncYougejianetSpecial($is_force = false)
    {
        /** zhihuishequ.yougejia.net定制 */
        $config = $this->getConfigData();
        $is_func_yougejianet_special = isset($config['func_yougejianet_special']) && $config['func_yougejianet_special'] == 1 ? 1 : 0;
        if ($is_force) {
            //走队列调用时 强制查询
            $is_func_yougejianet_special = 0;
            $whereArr = array();
            $whereArr[] = ['name', '=', 'func_yougejianet_special'];
            $oneTmp = $this->configObj->getOne($whereArr, 'value');
            if ($oneTmp && !$oneTmp->isEmpty()) {
                $is_func_yougejianet_special = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $is_func_yougejianet_special;
    }
    
    public function getFuncParkDevHxt85Customized()
    {
        /** 小红马华夏t85车闸设备定制定制 */
        $config = $this->getConfigData();
        $park_dev_hxt85_customized = isset($config['park_dev_hxt85_customized']) && $config['park_dev_hxt85_customized'] == 1 ? 1 : 0;
        return $park_dev_hxt85_customized;
    }
    
    public function getFuncZhihuilantiancomSpecial($is_force = false)
    {
        /** www.zhihuilantian.com定制 */
        $config = $this->getConfigData();
        $is_func_lantiancom_special = isset($config['func_zhihuilantiancom_special']) && $config['func_zhihuilantiancom_special'] == 1 ? 1 : 0;
        if ($is_force) {
            //走队列调用时 强制查询
            $is_func_lantiancom_special = 0;
            $whereArr = array();
            $whereArr[] = ['name', '=', 'func_zhihuilantiancom_special'];
            $oneTmp = $this->configObj->getOne($whereArr, 'value');
            if ($oneTmp && !$oneTmp->isEmpty()) {
                $is_func_lantiancom_special = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $is_func_lantiancom_special;

    }

    public function getFuncYumenLaoSpecial($is_force = false)
    {
        /** 玉门市老市区管委会定制 */
        $config = $this->getConfigData();
        $is_func_yumenlao_special = isset($config['func_yumenlao_special']) && $config['func_yumenlao_special'] == 1 ? 1 : 0;
        if ($is_force) {
            //走队列调用时 强制查询
            $is_func_yumenlao_special = 0;
            $whereArr = array();
            $whereArr[] = ['name', '=', 'func_yumenlao_special'];
            $oneTmp = $this->configObj->getOne($whereArr, 'value');
            if ($oneTmp && !$oneTmp->isEmpty()) {
                $is_func_yumenlao_special = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $is_func_yumenlao_special;
    }
    
    public function getFuncYumenLaoSpecialMeter($is_force = false)
    {
        /** 玉门市老市区管委会定制 */
        $config = $this->getConfigData();
        $is_func_yumenlao_special_meter = isset($config['func_yumenlao_special_meter']) && $config['func_yumenlao_special_meter'] == 1 ? 1 : 0;
        if ($is_force) {
            //走队列调用时 强制查询
            $is_func_yumenlao_special_meter = 0;
            $whereArr = array();
            $whereArr[] = ['name', '=', 'func_yumenlao_special_meter'];
            $oneTmp = $this->configObj->getOne($whereArr, 'value');
            if ($oneTmp && !$oneTmp->isEmpty()) {
                $is_func_yumenlao_special_meter = isset($oneTmp['value']) && $oneTmp['value'] == 1 ? 1 : 0;
            }
        }
        return $is_func_yumenlao_special_meter;
    }
    
    public function getStandardBindImportBtnJudge(){
        $config = $this->getConfigData();
        $standard_bind_import_btn = isset($config['standard_bind_import_btn']) && $config['standard_bind_import_btn'] == 1 ? 1 : 0;
        return $standard_bind_import_btn;
    }

    public function getFuncCloudmrsqfwSpecial()
    {
        /** cloud.mrsqfw.com 定制 */
        $config = $this->getConfigData();
        $is_func_cloudmrsqfw_special = isset($config['func_cloudmrsqfw_special']) && $config['func_cloudmrsqfw_special'] == 1 ? 1 : 0;
        return $is_func_cloudmrsqfw_special;
    }
    
}
