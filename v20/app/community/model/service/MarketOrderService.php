<?php


namespace app\community\model\service;


class MarketOrderService
{
    // 获取支付方式
    public function pay_method($is_config=false, $is_app=true) {
        $pay_method = array();
        if($is_app && 1==cfg('pay_weixinh5_open') && !empty(cfg('pay_weixinh5_appid')) && !empty(cfg('pay_weixinh5_mchid')) && !empty(cfg('pay_weixinh5_key')) && !empty(cfg('pay_weixinh5_appsecret'))){
            $weixin_h5 =  array (
                'name' => '微信h5支付'
            );
            if ($is_config) {
                $weixin_h5['config'] = array (
                    'pay_weixinh5_open' => cfg('pay_weixinh5_open'),
                    'pay_weixinh5_appid' => cfg('pay_weixinh5_appid'),
                    'pay_weixinh5_mchid' => cfg('pay_weixinh5_mchid'),
                    'pay_weixinh5_key' => cfg('pay_weixinh5_key'),
                    'pay_weixinh5_appsecret' => cfg('pay_weixinh5_appsecret')
                );
            }
            $pay_method['weixinh5'] = $weixin_h5;
        }
        if (1==cfg('pay_market_offline')) {
            $offline =  array (
                'name' => '线下支付'
            );
            if ($is_config) {
                $weixin_h5['config'] = array (
                    'pay_market_offline' => cfg('pay_market_offline')
                );
            }
            $pay_method['offline'] = $offline;
        }
        return $pay_method;
    }
}