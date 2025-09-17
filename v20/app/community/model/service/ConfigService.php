<?php
/**
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/29 21:02
 */

namespace app\community\model\service;

use app\community\model\db\Config;

class ConfigService
{
    /**
     * 获取数据库配置
     * @author: wanziyang
     * @date_time: 2020/4/30 9:11
     * @param string $name 配置项名称
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_config($name,$field = true) {
        // 初始化 物业管理员 数据层
        $db_config = new Config();
        $info = $db_config->get_one($name,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 条件获取数据库配置
     * @author: wanziyang
     * @date_time: 2020/4/30 9:13
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_config_list($where,$field = 'name,value') {
        // 初始化 物业管理员 数据层
        $db_config = new Config();
        $configs = $db_config->get_list($where,$field);
        $config = [];
        if (!($configs->isEmpty())) {
            foreach($configs as $key=>$value){
                $config[$value['name']] = $value['value'];
            }
            if (isset($config['Currency_txt'])) {
                $config['Currency_txt'] = $config['Currency_txt'] ? $config['Currency_txt'] : '元';
            }
            if (isset($config['Currency_symbol'])) {
                $config['Currency_symbol'] = $config['Currency_symbol'] ? $config['Currency_symbol'] : '￥';
            }
        }
        return $config;
    }

    public function get_pay_method($notOnline=0,$notOffline=0,$is_wap=false,$is_app=false,$is_all=false,$is_refund=false){
        $tmp_config_list = $this->get_gid_config(7);
        $tmp_config_list_app = $this->get_gid_config(23);
        $tmp_config_list_tl = $this->get_gid_config(60);

        foreach($tmp_config_list as $key=>$value){
            if(in_array($value['tab_id'],array('paylinx','wepayez','fubei'))){
                continue;
            }
            $config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
            $config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
            if(strpos($value['name'],'service_charge_open') !== false){
                $config_list[$value['tab_id']]['config']['service_charge_open'] = $value['value'];
//                unset($value);
            }
            if(strpos($value['name'],'discount_tips') !== false){
                $config_list[$value['tab_id']]['config']['discount_tips'] = $value['value'] ? $value['value'] : '';
//                unset($value);
            }
            if(strpos($value['name'],'service_charge') !== false){
                $config_list[$value['tab_id']]['config']['service_charge'] = $value['value'] ? $value['value'] : 0;
//                unset($value);
            }
            if(strpos($value['name'],'rates') !== false){
                $config_list[$value['tab_id']]['config']['rates'] = $value['value'] ? $value['value'] : 0;
                unset($value);
            }
        }
        foreach($tmp_config_list_app as $key=>$value){
            $config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
            $config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
            if(strpos($value['name'],'service_charge_open') !== false){
                $config_list[$value['tab_id']]['config']['service_charge_open'] = $value['value'];
//                unset($value);
            }
            if(strpos($value['name'],'discount_tips') !== false){
                $config_list[$value['tab_id']]['config']['discount_tips'] = $value['value'];
//                unset($value);
            }
            if(strpos($value['name'],'service_charge') !== false){
                $config_list[$value['tab_id']]['config']['service_charge'] = $value['value'];
//                unset($value);
            }
            if(strpos($value['name'],'rates') !== false){
                $config_list[$value['tab_id']]['config']['rates'] = $value['value'] ? $value['value'] : 0;
                unset($value);
            }
        }
        foreach($tmp_config_list_tl as $key=>$value){
            $config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
            $config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
        }
        //剔除已关闭的支付
        foreach($config_list as $key=>$value){
            $pigcms_key = 'pay_'.$key.'_open';
            if(empty($value['config'][$pigcms_key]) || (!$is_all && $is_wap && $key == 'chinabank') || (!$is_all && !$is_refund && $is_wap && $key == 'alipay' && $value['config'][$pigcms_key] == 3) || (!$is_all && !$is_refund && empty($is_wap) && $key == 'alipay' && $value['config'][$pigcms_key] == 2)){
                unset($config_list[$key]);
            }else{
                $tmp_alias = 'pay_'.$key.'_alias_name';
                if(!empty($value['config'][$tmp_alias])){
                    $config_list[$key]['name'] = $value['config'][$tmp_alias];
                }
            }
        }
        if (!$is_all && (!$is_wap||$is_app)) unset($config_list['alipayh5']);
        if (!$is_all && isset($config_list['alipayh5']) && $config_list['alipayh5']['config']['pay_alipayh5_open'] && $config_list['alipayh5']['config']['pay_alipayh5_appid'] && $config_list['alipayh5']['config']['pay_alipayh5_merchant_private_key'] && $config_list['alipayh5']['config']['pay_alipayh5_public_key']) {
            unset($config_list['alipay']);
        }
        if($notOffline && $config_list['offline']){
            unset($config_list['offline']);
        }
        if($notOnline){
            $new_config_list = array();
            if($config_list['offline']){
                $new_config_list['offline'] = $config_list['offline'];
            }
            $config_list = $new_config_list;
        }

        return $config_list;
    }
    public function get_gid_config($gid){
        $condition_config['gid'] = $gid;
        //$config = $this->field(true)->where($condition_config)->order('`sort` DESC')->select();
        $db_config = new Config();
        $config = $db_config->get_list($condition_config);
        return $config;
    }
}