<?php


namespace app\community\model\service;

use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillagePayConfig;

class PayService
{
    /**
     * 获取小区抽成返点和商户id
     * @author lijie
     * @date_time 2021/05/25
     * @param int $village_id
     * @return mixed
     */
    public function getConfig($village_id=0)
    {
        $db_house_village = new HouseVillage();
        $db_house_village_pay_config  = new HouseVillagePayConfig();
        $village_info = $db_house_village->getOne($village_id,'property_id,percent');
        $house_pay_config = $db_house_village_pay_config->getOne(['village_id'=>$village_id]);
        if(empty($house_pay_config)){
            $house_pay_config = $db_house_village_pay_config->getOne(['property_id'=>$village_info['property_id']]);
        }
        if($village_info['percent'] > 0){
            $percent = $village_info['percent'];
        } else{
            $service_config = new ConfigService();
            $shequ_info = $service_config->get_config('platform_get_village_percent','value');
            $percent = $shequ_info['value'];
        }
        $data['mid'] = isset($house_pay_config['mid'])?$house_pay_config['mid']:'';
        $data['percent'] = $percent;
        return $data;
    }


    /**
     * 获取四川农村信用社商户号
     * @author lijie
     * @date_time 2021/08/21
     * @param int $village_id
     * @return bool|string
     */
    public function getVillageScrcu($village_id = 0)
    {
        if(!$village_id){
            return false;
        }
        $db_house_village = new HouseVillage();
        $db_house_village_pay_config  = new HouseVillagePayConfig();
        $village_info = $db_house_village->getOne($village_id,'property_id,percent');
        $house_pay_config = $db_house_village_pay_config->getOne(['village_id'=>$village_id]);
        if(empty($house_pay_config) || !isset($house_pay_config['scrcu_mer_no']) || empty($house_pay_config['scrcu_mer_no'])){
            $house_pay_config = $db_house_village_pay_config->getOne(['property_id'=>$village_info['property_id']]);
        }
        $scrcu_mer_no = $house_pay_config && isset($house_pay_config['scrcu_mer_no']) ? $house_pay_config['scrcu_mer_no']:'';
        return $scrcu_mer_no;
    }
    
    public function getVillageScrcuMerNo($village_id = 0)
    {
        if(!$village_id){
            return array();
        }
        $db_house_village = new HouseVillage();
        $db_house_village_pay_config  = new HouseVillagePayConfig();
        $village_info = $db_house_village->getOne($village_id,'property_id,percent');
        $house_pay_config = $db_house_village_pay_config->getOne(['village_id'=>$village_id]);
        if(empty($house_pay_config) || !isset($house_pay_config['scrcu_mer_no'])  || empty($house_pay_config['scrcu_mer_no'])){
            $house_pay_config = $db_house_village_pay_config->getOne(['property_id'=>$village_info['property_id']]);
        }
        $pay_config=array();
        if($house_pay_config && !$house_pay_config->isEmpty()){
            $pay_config= $house_pay_config->toArray();
        }
        return $pay_config;
    }
}