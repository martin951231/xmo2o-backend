<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/9/26 10:22
 */

namespace app\community\controller\village_api;
use app\community\controller\CommunityBaseController;
use app\community\model\service\AdminLoginService;
use app\community\model\service\SkyCockpitInfoService;
class SkyCockpitInfoController extends CommunityBaseController{

    public function getCockpitDatas(){
        $skyCockpitInfoService = new SkyCockpitInfoService();
        try{
            $whereArr=array();
            $whereArr[]=array('id','=',1);
            $res=$skyCockpitInfoService->getOneData($whereArr);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }

    }

}