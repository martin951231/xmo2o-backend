<?php


class ChinaumsModel extends Model
{
    /**
     * 获取小区抽成返点和商户id
     * @param int $village_id
     * @return bool
     */
    public function getConfig($village_id=0)
    {
        if(!$village_id)
            return false;
        $village_info = D("House_village")->get_village_info($village_id,'property_id,percent');
        $house_pay_config = M('House_village_pay_config')->where(['village_id'=>$village_id])->find();

        if(empty($house_pay_config)){
            $house_pay_config = M('House_village_pay_config')->where(['property_id'=>$village_info['property_id']])->find();
        }
        if($village_info['percent'] > 0)
            $percent = $village_info['percent'];
        else
            $percent = $percent = C('config.platform_get_village_percent');
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
    public function getVillageScrcu($village_id=0)
    {
        if(!$village_id)
            return false;
        $village_info = D("House_village")->get_village_info($village_id,'property_id,percent');
        $house_pay_config = M('House_village_pay_config')->where(['village_id'=>$village_id])->find();
        if(empty($house_pay_config)){
            $house_pay_config = M('House_village_pay_config')->where(['property_id'=>$village_info['property_id']])->find();
        }
        $scrcu_mer_no = isset($house_pay_config['scrcu_mer_no'])?$house_pay_config['scrcu_mer_no']:'';
        return $scrcu_mer_no;
    }

}