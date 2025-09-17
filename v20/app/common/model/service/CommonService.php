<?php
/**
 * 公共service
 */

namespace app\common\model\service;

use app\merchant\model\service\MerchantService;
use token\Token;

class CommonService
{

    /**
     * 平台伪登录商家
     * @param $mer_id 商家mer_id 
     */
    public function platformLoginMerchant($mer_id)
    {
        $merchantUser = (new MerchantService)->getOne(['mer_id' => $mer_id]);
        if(!$merchantUser) {
            throw new \think\Exception(L_("商家不存在"));
        }
        if($merchantUser['status'] == 0 || $merchantUser['status'] == 2) {
            throw new \think\Exception(L_("该商家的状态不存在！请查阅"));
        }
        // 生成ticket
        $ticket = Token::createToken($mer_id);
        if(!$ticket){
            throw new \think\Exception(L_("登陆失败,请重试！"));
        }
        $merchantUser['login_type'] = "fake_login";
        session('merchant',$merchantUser);
        session('staff',null);
        cookie('merchant_access_token', $ticket);


        $returnArr = [];
        $returnArr['ticket'] = $ticket;
        return $returnArr;
    }


    //获取天气  '合肥','117.271133,31.884772'
    public function getWeather($areaName, $lngLat)
    {
        if (empty($areaName) || empty($lngLat)) {
            return [];
        }
        return (new \weather\Weather())->getWeatherInfo($areaName, $lngLat);
    }
}