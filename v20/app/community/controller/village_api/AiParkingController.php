<?php
/**
 * @author : 
 * @date : 2024/10/18
 */
namespace app\community\controller\village_api;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseNewAiParkingService;

class AiParkingController extends CommunityBaseController{
    public function getThirdAihorseGarage(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output_error(1002, '请先登录小区后台');
        }
        try{
            $houseNewAiParkingServic=new HouseNewAiParkingService();
            $res=$houseNewAiParkingServic->handleAihorsePlatformGarage($village_id);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1001, '拉取数据异常！');
        }
    }

    public function getAihorseThirdGarageInfo(){
        $village_id = $this->adminUser['village_id'];
        $park_key = $this->request->post('park_key','','trim');
        $garage_id = $this->request->post('garage_id',0,'intval');
        if (empty($village_id)){
            return api_output_error(1002, '请先登录小区后台');
        }
        try{
            $houseNewAiParkingServic=new HouseNewAiParkingService();
            $res=$houseNewAiParkingServic->handleAihorsePlatformGarageDetail($village_id,$park_key,$garage_id);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1001, $e->getMessage());
        }
    }

    public function getAihorseGaragePassageList(){
        $village_id = $this->adminUser['village_id'];
        $park_key = $this->request->post('park_key','','trim');
        $garage_id = $this->request->post('garage_id',0,'intval');
        if (empty($village_id)){
            return api_output_error(1002, '请先登录小区后台');
        }
        if(empty($park_key)){
            return api_output_error(1001, '车场唯一编号为空，请检查！');
        }
        if($garage_id<1){
            return api_output_error(1001, '车场ID参数错误！');
        }
        try{
            $houseNewAiParkingServic=new HouseNewAiParkingService();
            $res=$houseNewAiParkingServic->handleAihorsePfGaragePassageList($village_id,$garage_id,$park_key);
            return api_output(0,$res);
        }catch (\Exception $e){
            return api_output_error(1001, $e->getMessage());
        }
    }

}