<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/25 13:27
 */
namespace app\community\controller\village_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageThirdSDKService;
use app\community\model\service\HouseVillageThirdService;

class HouseVillageThirdController  extends CommunityBaseController
{
    public function test(){
        $service_house_village_third_sdk = new HouseVillageThirdService();
        $res=$service_house_village_third_sdk->insertOrUpdateCommunity(50);
        print_r($res);exit;
     /*   $data=[];
       //  $data['clientId']='1d9108966a95b2593db4168072be56dd';
        $data['token']='F1678EC35685DD2801BB8F5CEEA499D7';
        $res=$service_house_village_third_sdk->getAllProvinceCityCounty($data);*/

        return api_output(0, $res);
    }

}